var WEM = WEM || {};
WEM.Grid  = WEM.Grid || {};
(function() {
    WEM.Grid.Drag = WEM.Grid.Drag || {
        selectors:{
            grid:'.grid_preview',
            gridItem:'.be_item_grid',
            firstLevelElements:'.grid_preview > .be_item_grid',
            allLevelElements:'.grid_preview .be_item_grid',
            breakpointSelector:'select[name="ctrl_select_breakpoints_"]',
            gridGapValue:'select[name="grid_gap[value]"]',
            gridGapUnit:'select[name="grid_gap[unit]"]',
            gridMode:'select[name="grid_mode"]',
        },
        breakpoints:['all','xl','lg','md','sm','xs','xxs'],
        gridMode:{
            automatic:'automatic',
            custom:'custom',
        },
        regexpBreakpoints:/(-xxs|-xs|-sm|-md|-lg|-xl)/,
        init:function(){
            self.applyListeners();
        }
        ,applyListeners:function(){
            document.querySelectorAll(self.selectors.allLevelElements).forEach(function (container){ // only first level elements, not nested ones
                if("false" !== container.getAttribute('draggable')){
                    var dragBtn = container.querySelector('.drag-handle');
                    if(null !== dragBtn){
                        dragBtn.addEventListener('dragstart',self.dragStart);
                        dragBtn.addEventListener('dragend',self.dragEnd);
                        dragBtn.addEventListener('dragover',self.dragOver);
                    }
                }

                if("false" !== container.getAttribute('dropable')){
                    container.setAttribute('dropable',true);
                    container.addEventListener('dragover',self.dragOver);
                    container.addEventListener('dragenter',self.dragEnter);
                    container.addEventListener('dragleave',self.dragLeave);
                    container.addEventListener('drop',self.drop);
                }
                // save the original number of columns
                for(var index in self.breakpoints){
                    var breakpoint = self.breakpoints[index];
                    var breakpointModifier = 'all' == breakpoint ? '' : '-'+breakpoint;
                    for(i = 1; i <=12;i++){
                        if(-1 < container.className.indexOf('cols-span'+breakpointModifier+'-'+i)){
                            container.setAttribute('data-cols-span'+breakpointModifier,i);
                        }
                    }
                }
            });
        }
        ,dragStart:function(event){
            if(-1 < event.target.className.indexOf('drag-handle')){
                var target = event.target.parentNode.parentNode;
            }else if('IMG' == event.target.nodeName){
                var target = event.target.parentNode.parentNode.parentNode;
            }
            event
                .dataTransfer
                .setData('text/plain', target.getAttribute('data-id'));
            event
                .dataTransfer
                .setDragImage(document.querySelector('[data-id="'+target.getAttribute('data-id')+'"]'),event.layerX,event.layerY);
        }
        ,dragEnd:function(event){
            event.preventDefault();
        }
        ,dragOver:function(event){
            event.preventDefault();
        }
        ,dragEnter:function(event){
            if(event.target && !event.target.getAttribute('dropable')){
                return;
            }
            event.target.classList.toggle('drag-enter', true);
        }
        ,dragLeave:function(event){
            if(event.target && !event.target.getAttribute('dropable')){
                return;
            }
            event.target.classList.toggle('drag-enter',false);
        }
        ,drop:function(event){
            event.preventDefault();
        
            var dropzone = event.target;
            var id = event
                .dataTransfer
                .getData('text');
            var draggableElement = document.querySelector('[data-id="'+id+'"]');
            var pid = dropzone.getAttribute('data-id');

            var gridSource = self.getGridFromElement(draggableElement);
            var gridDest = self.getGridFromElement(dropzone);

            event.target.classList.toggle('drag-enter',false);
            draggableElement.classList.toggle('drag-enter',false);

            if(!dropzone.getAttribute('dropable')
            || id == pid
            ){
                return;
            }

            var requests = [];
            var doDoublePositionning = true;
            var position = 'before';

            if('fake-last-element' == dropzone.getAttribute('data-type')){
                pid = self.getGridLastRealElement(dropzone).getAttribute('data-id');
                doDoublePositionning = !self.isGridFirstLevel(gridDest);
            }else if('fake-first-element' == dropzone.getAttribute('data-type')){
                var previousDropzone = dropzone;
                dropzone = self.getGridFirstRealElement(dropzone);
                if(dropzone == draggableElement){
                    dropzone = previousDropzone;
                    position = 'after';
                }

                gridDest = self.getGridFromElement(dropzone);
                pid = dropzone.getAttribute('data-id');
                // pid = self.getGridFirstRealElement(dropzone).getAttribute('data-id');
                // doDoublePositionning = !(self.isGridFirstLevel(gridSource) && self.isGridFirstLevel(gridDest));
            }else if(dropzone.previousSibling == draggableElement){
                if(self.isGridFirstLevel(gridSource) && self.isGridFirstLevel(gridDest)){
                    doDoublePositionning=false;
                    position = 'after';
                }
            }

            if(id == pid){
                return;
            }

            if('grid-start' == dropzone.getAttribute('data-type') 
            && 'after' == position
            ){
                var gridStopElements = dropzone.querySelectorAll('[data-type="grid-stop"]');
                pid = gridStopElements[gridStopElements.length-1].getAttribute('data-id');
            }

            if('grid-start' == draggableElement.getAttribute('data-type')){
                // if we move a grid-start, we have to move all children elements before the dropzone
                // move the grid start
                requests.push(self.getContaoRequestPutElementAfterAnother(id, pid));
                if(self.isGridFirstLevel(gridSource) && !self.isGridFirstLevel(gridDest)){
                    requests.push(self.getContaoRequestPutElementAfterAnother(pid, id)); // comment to make subgrid -> grid work on last fake element
                }
                
                // move the grid elements
                pid = id; // the grid start becomes the PID
                var gridElements = draggableElement.querySelectorAll('div[data-type]');
                gridElements.forEach(function(gridElement){
                    if(-1 == gridElement.getAttribute('data-type').indexOf('fake-')){
                        id = gridElement.getAttribute('data-id');
                        requests.push(self.getContaoRequestPutElementAfterAnother(id, pid));
                        pid = id; // grid elements stay behind each others
                    }
                });
                if(doDoublePositionning){
                    requests.push(self.getContaoRequestPutElementAfterAnother(dropzone.getAttribute('data-id'), pid));
                }
            }else{
                requests.push(self.getContaoRequestPutElementAfterAnother(id, pid));
                if(doDoublePositionning){
                    requests.push(self.getContaoRequestPutElementAfterAnother(pid, id));
                }
            }

            self.runFakeQueue(requests);

            // once done, exchange both element places in display
            gridSource.removeChild(draggableElement);
            if('before' === position){
                if('fake-last-element' == dropzone.getAttribute('data-type') && self.isGridFirstLevel(gridDest)){ //because the 'add element' is inside the grid, right before the fake last element
                    self.getGridLastRealElement(dropzone).after(draggableElement);
                }else{
                    gridDest.insertBefore(draggableElement,dropzone);
                }
            }else{
                gridDest.insertBefore(draggableElement,dropzone.nextSibling);

            }

            var selectBreakpoints = document.querySelector(self.selectors.breakpointSelector);

            var currentGridMode = document.querySelector(self.selectors.gridMode).value;
            var currentBreakpoint = null !== selectBreakpoints ? selectBreakpoints.value : 'all';
            var inputNbCols = self.getInputNumberOfColumnsForBreakpoint(currentBreakpoint);

            self.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), currentGridMode, inputNbCols ? inputNbCols.getAttribute('data-breakpoint') : 'all', inputNbCols ? inputNbCols.value : 12);
            // things to do :
            // - hide (or not) selects for cols/rows
            // - adapt item classes (if moving from a "custom" to "automatic" grid, or vice-versa)
            self.updateGridElementsSelectNbColumnsVisibility(self.gridMode.automatic === gridDest.getAttribute('data-grid-mode') ? '' : currentBreakpoint);
            self.updateItemDataClass(draggableElement,self.gridMode.automatic === gridDest.getAttribute('data-grid-mode') ? '' : currentBreakpoint);
        }
        ,getInputNumberOfColumnsForBreakpoint(breakpoint){
            for(var i = 0; i<=6;i++){
                var input = document.querySelector('[name="grid_cols['+i+'][value]"]');
                if(input && breakpoint == input.getAttribute('data-breakpoint')){
                    return input;
                }
            }
            return null;
        }
        ,getGridFirstRealElement:function(fromElement){
            var grid = self.getGridFromElement(fromElement);

            var elements = grid.querySelectorAll('div[data-type]');

            var elementIndex = 0;
            var element = elements[elementIndex];
            while(-1 < element.getAttribute('data-type').indexOf('fake-') && elementIndex < elements.length){
                elementIndex++;
                element = elements[elementIndex];
            }

            return -1 < element.getAttribute('data-type').indexOf('fake-') ? null : element;
        }
        ,getGridLastRealElement:function(fromElement){
            var grid = self.getGridFromElement(fromElement);

            var elements = grid.querySelectorAll('div[data-type]');

            var elementIndex = elements.length-1;
            var element = elements[elementIndex];
            while(-1 < element.getAttribute('data-type').indexOf('fake-') && elementIndex > 0){
                elementIndex--;
                element = elements[elementIndex];
            }
            if('grid-start' == element.getAttribute('data-type')){
                var gridStops = element.querySelectorAll('[data-type="grid-stop"]');
                element = gridStops[gridStops.length-1];
            }

            return -1 < element.getAttribute('data-type').indexOf('fake-') ? null : element;
        }
        ,getGridFromElement:function(element){
            if(-1 < element.className.indexOf(self.selectors.grid.substring(1))
            // ||  -1 < element.className.indexOf('d-grid')
            || -1 < element.className.indexOf('ce_grid-start')
            ){
                return element;
            }else{
                element = self.getGridFromElement(element.parentNode); 
            }
            return element;
        }
        ,isGridFirstLevel:function(element){
            return -1 < element.className.indexOf(self.selectors.grid.substring(1));
        }
        ,getContaoRequestPutElementAfterAnother:function(id, pid, params = {}){
            var req,href;
            req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=cut&mode=1&pid=' + pid;
            href = window.location.href.replace(/\?.*$/, '');
            params = Object.assign(params, {'url':href + req, 'followRedirects':false,'id':id,'pid':pid});
            return params;
        }
        ,runFakeQueue:function(requests){
            if(requests.length <= 0){
                return;
            }
            AjaxRequest.displayBox(Contao.lang.loading + ' …');
            self.runFakeQueueItem(requests,0);
        }
        ,runFakeQueueItem:function(requests, index){
            fetch(requests[index].url,{
                method:'get',
                redirect:'manual'
            })
            .then(data => {
                if("undefined" != typeof requests[index+1]){
                    self.runFakeQueueItem(requests,index+1);
                }else{
                    AjaxRequest.hideBox();
                }
            })
            .catch(error => {
                AjaxRequest.hideBox();
            });
        }
        ,updateGridElementsAvailableColumns:function(grid, gridMode, breakpoint, nbColumns){
            nbColumns = parseInt(nbColumns);
            if(self.gridMode.custom === gridMode && (isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns)){
                return;
            }
            // Update the items' available size options
            grid.querySelectorAll(':scope > .be_item_grid').forEach(function(item){
                if("grid-start" === item.getAttribute('data-type')){
                    self.updateGridElementsAvailableColumns(item.querySelector('.ce_grid-start'), item.getAttribute('data-grid-mode'), breakpoint, item.getAttribute('data-nb-cols'));
                }

                if(self.gridMode.automatic === gridMode){

                }else if(self.gridMode.custom === gridMode){
                    var select = item.querySelector('select[name="grid_items['+item.getAttribute('data-id')+'_cols]['+breakpoint+']"]');
                    var dataAttributeName='data-cols-span'+('all' == breakpoint ? '' : '-'+breakpoint);
                    // var dataAttributeName='data-cols-span';
                    var classNameBase='cols-span'+('all' == breakpoint ? '' : '-'+breakpoint)+'-';

                    if(null === select){
                        return;
                    }
                    var inheritedResolution = self.getClosestHigherResolutionDefinedForItemIdAndTypeAndBreakpoint(select.getAttribute('data-item-id'),'cols',breakpoint);
                    var inheritedValue = self.getNbColumnsOrRowsFromCssClass(
                        self.getClosestHigherResolutionDefinedValueForItemIdAndTypeAndBreakpoint(select.getAttribute('data-item-id'),'cols',breakpoint)
                    );
                    var valueBeforeReconstruct = self.getNbColumnsOrRowsFromCssClass(select.value);
                    var selectedIndexBeforeReconstruct = select.options.selectedIndex;
                    // remove all options
                    var length = select.options.length;
                    for(i = 0; i <= length; i++){
                        select.remove(0);
                    }
                    // recreate options
                    if('all' != breakpoint){
                        select.add(new Option(self.buildInheritedOptionTextForTypeAndBreakpoint('cols',inheritedResolution,inheritedValue),'',false,"" == valueBeforeReconstruct ? true : false));
                    }else{
                        select.add(new Option('-','',false,"" == valueBeforeReconstruct ? true : false));
                    }
                    for(var i = 1; i <= nbColumns; i++){
                        select.add(new Option(WEM.Grid.Translations.columns[i-1],classNameBase+i,false,parseInt(valueBeforeReconstruct) == i ? true : false));
                    }

                    for(i = 1; i <= 12; i++){
                        if(-1 < item.className.indexOf(classNameBase+i) && i > nbColumns){
                            item.classList.toggle(classNameBase+i,true);
                        }
                    }
                    
                    if(0 >= selectedIndexBeforeReconstruct){
                        select.value = '';
                    }else if("" != valueBeforeReconstruct){
                        if(parseInt(valueBeforeReconstruct) <= nbColumns){
                            select.value = self.buildCssClassFromTypeAndBreakpointAndNb('cols',breakpoint,valueBeforeReconstruct);
                        }else{
                            select.value = self.buildCssClassFromTypeAndBreakpointAndNb('cols',breakpoint,nbColumns);
                        }
                    }
                    select.dispatchEvent(new Event('change_auto'));
                }
                
            });
        },
        getNbColumnsOrRowsFromCssClass:function(cssClass){
            cssClass = cssClass.replace('cols-','')
            .replace('rows-','')
            .replace('span-','')
            .replace('offset-','')
            .replace('xl-','')
            .replace('lg-','')
            .replace('md-','')
            .replace('sm-','')
            .replace('xxs-','') // before "xs" otherwise "xxs" becomes "x"
            .replace('xs-','')
            ;

            return cssClass;
        },
        buildCssClassFromTypeAndBreakpointAndNb:function(type,breakpoint,nb){
            let cssClass = type+'-span-'+("all" === breakpoint ? '' : breakpoint +'-')+nb;

            return cssClass;
        },
        getClosestHigherResolutionDefinedForItemIdAndTypeAndBreakpoint:function(itemId, type, breakpoint){
            var resolutions = self.getHigherResolutions(breakpoint);
            var selectedResolution = 'all'; //fallback
            var found = false;
            resolutions.forEach(function(resolution){
                if(resolution != breakpoint){
                    var select = self.getSelectForItemIdAndTypeAndBreakpoint(itemId, type, resolution);
                    if(!found && select.value !== ''){
                        selectedResolution = resolution;
                        found = true;
                    }
                }
            });
            return selectedResolution;
        },
        getClosestHigherResolutionDefinedValueForItemIdAndTypeAndBreakpoint:function(itemId, type, breakpoint){
            var resolution = self.getClosestHigherResolutionDefinedForItemIdAndTypeAndBreakpoint(itemId, type, breakpoint);
            var select = self.getSelectForItemIdAndTypeAndBreakpoint(itemId, type, resolution);
            
            return select.value;
        },
        getLowerResolutions:function(breakpoint){
            var indexOfResolution = self.breakpoints.indexOf(breakpoint);

            return self.breakpoints.slice(indexOfResolution);
        },

        getHigherResolutions:function(breakpoint){
            var breakpoints = self.breakpoints.slice(); // to have a copy by value
            var indexOfResolution = breakpoints.reverse().indexOf(breakpoint);
            var higherResolutions = breakpoints.slice(indexOfResolution+1);
            return higherResolutions;
        },

        getSelectForItemIdAndTypeAndBreakpoint:function(itemId, type, breakpoint){
            return document.querySelector('[data-item-id="'+itemId+'"][data-type="'+type+'"][data-breakpoint="'+breakpoint+'"]');
        },
        getClosestHigherResolutionDefinedForGrid:function(breakpoint){
            var resolutions = self.getHigherResolutions(breakpoint);
            var selectedResolution = 'all'; //fallback
            var found = false;
            resolutions.forEach(function(resolution){
                if(resolution != breakpoint){
                    var select = self.getSelectForBreakpointForGrid(resolution);
                    if(!found && select.value !== ''){
                        selectedResolution = resolution;
                        found = true;
                    }
                }
            });
            return selectedResolution;
        },
        buildInheritedOptionTextForTypeAndBreakpoint:function(type, breakpoint, value){
            if("" == value){
                value = 1;
            }
            if('cols' == type){
                return WEM.Grid.Translations.inheritedColumns.replace('%s',value).replace('%s',WEM.Grid.Translations.breakpoints[breakpoint.toLowerCase()]);
            }else if('rows' == type){
                return WEM.Grid.Translations.inheritedRows.replace('%s',value).replace('%s',WEM.Grid.Translations.breakpoints[breakpoint.toLowerCase()]);
            }else{
                return 'Error : type unknown';
            }
        },
        getSelectForBreakpointForGrid:function(breakpoint){
            var i = WEM.Grid.Drag.breakpoints.indexOf(breakpoint);
            return document.querySelector('[name="grid_cols['+i+'][value]"]');
        },
        updateItemDataClass:function(itemgrid, breakpoint){
            var parentGrid = self.getParentGridElement(itemgrid, itemgrid);
            var rowsSelect= itemgrid.querySelector('select[data-type="rows"][data-item-id="'+itemgrid.getAttribute('data-id')+'"][data-breakpoint="'+breakpoint+'"]');
            var colsSelect = itemgrid.querySelector('select[data-type="cols"][data-item-id="'+itemgrid.getAttribute('data-id')+'"][data-breakpoint="'+breakpoint+'"]');
            var rowsClass = '';
            if(null !== rowsSelect){
                rowsClass = rowsSelect.value;

                if('' == rowsSelect.value){
                    rowsClass = self.getClosestHigherResolutionDefinedValueForItemIdAndTypeAndBreakpoint(
                        rowsSelect.getAttribute('data-item-id'),
                        'rows',
                        breakpoint
                    );
                }
            }
                
            rowsClass = rowsClass.replace(self.regexpBreakpoints,'');

            var colsClass = '';
            if(null !== colsSelect){
                colsClass = colsSelect.value;
                if('' == colsSelect.value){
                    colsClass = self.getClosestHigherResolutionDefinedValueForItemIdAndTypeAndBreakpoint(
                        colsSelect.getAttribute('data-item-id'),
                        'cols',
                        breakpoint
                    );
                }
            }
            colsClass = colsClass.replace(self.regexpBreakpoints,'');

            var strClass = itemgrid.querySelector('input[data-item-id="'+itemgrid.getAttribute('data-id')+'"]').value;
            if(self.gridMode.custom === parentGrid.getAttribute('data-grid-mode')){
                strClass+=' '
                + rowsClass
                + ' ' 
                + colsClass;
            }
            itemgrid.setAttribute('class', itemgrid.getAttribute('data-class')+' '+strClass.replace('hidden','wem_hidden'));
        },
        getParentGridItemElement:function(element){
            if(!element.classList.contains('be_item_grid')){
                return self.getParentGridItemElement(element.parentNode);
            }

            return element;
        }, 
        getParentGridElement:function(element,elementSource){
            if(!element.classList.contains('be_subgrid') && !element.classList.contains('grid_preview')){
                return self.getParentGridElement(element.parentNode, elementSource);
            }else if(elementSource === element){
                return self.getParentGridElement(element.parentNode, elementSource);
            } 

            return element;
        },
        changeLowerResolutionValues:function(itemId, type, breakpoint, value, triggerSave){
            let resolutions = WEM.Grid.Drag.getLowerResolutions(breakpoint);
            resolutions.forEach(function(resolution){
                if(breakpoint != resolution){
                    var select = WEM.Grid.Drag.getSelectForItemIdAndTypeAndBreakpoint(itemId, type, resolution);
                    if(null == select){
                        console.log(itemId, type, resolution);
                    }else{
                        var higherDefinedRes = WEM.Grid.Drag.getClosestHigherResolutionDefinedForItemIdAndTypeAndBreakpoint(itemId, type, resolution);
                        var selectValue = WEM.Grid.Drag.getNbColumnsOrRowsFromCssClass(
                            WEM.Grid.Drag.getClosestHigherResolutionDefinedValueForItemIdAndTypeAndBreakpoint(itemId, type, resolution)
                        );
                        select.options[0].innerHTML = WEM.Grid.Drag.buildInheritedOptionTextForTypeAndBreakpoint(type, higherDefinedRes, selectValue);
                    }
                }
            });
        },
        updateMainGridGap:function(gapValue,gapUnit){
            var grid = document.querySelector(WEM.Grid.Drag.selectors.grid);
            if(-1 < grid.className.indexOf('gap')){
                grid.className = grid.className.replace(/gap-([0-6]{1})([-rem]{0,4})/,'gap-'+gapValue+('' != gapUnit ? '-'+gapUnit : ''));
            }else{
                grid.className = grid.className.concat('gap-'+gapValue+('' != gapUnit ? '-'+gapUnit : ''));
            }
        },
        updateMainGridNbOfColumns:function(nbColumns){
            var grid = document.querySelector(WEM.Grid.Drag.selectors.grid);
            grid.className = grid.className.replace(/cols-([0-9]{1,2})/,'cols-'+nbColumns);
        },
        updateMainGridFakeElementsNbOfColumns:function(nbColumns){
            document.querySelectorAll(WEM.Grid.Drag.selectors.grid + ' > .be_item_grid_fake').forEach(function(item){
                item.className = item.className.replace(/cols-span-([0-9]{1,2})/,'cols-span-'+nbColumns);
            });
        },
        updateGridElementsSelectNbColumnsVisibility:function(breakpoint){
            document.querySelectorAll(WEM.Grid.Drag.selectors.grid + ' select').forEach(function(item){
                if(null != item.getAttribute('data-breakpoint')){
                    var grid = self.getGridFromElement(item);
                    var shouldBeHidden = breakpoint != item.getAttribute('data-breakpoint') || "1" == item.getAttribute('data-force-hidden') || self.gridMode.automatic === grid.getAttribute('data-grid-mode');
                    item.classList.toggle('hidden', shouldBeHidden);
                    document.querySelector('label[for="'+item.id+'"]').classList.toggle('hidden', shouldBeHidden);
                    if(breakpoint == item.getAttribute('data-breakpoint')){
                        item.dispatchEvent(new Event('change_auto'));
                    }
                }
            });
        }
    }
    var self = WEM.Grid.Drag;
})();
var WEM = WEM || {};
WEM.Grid  = WEM.Grid || {};
(function() {
    WEM.Grid.Saver = WEM.Grid.Saver || {
        saveItemCols:function(itemId, value, breakpoint){
            return self.save({id: itemId, property: 'cols', value: value, breakpoint: breakpoint});
        }
        ,saveItemRows:function(itemId, value, breakpoint){
            return self.save({id: itemId, property: 'rows', value: value, breakpoint: breakpoint});
        }
        ,saveItemClass:function(itemId, value){
            return self.save({id: itemId, property: 'classes', value: value});
        }
        ,save:function(params){
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            var url = 'contao/grid-builder?grid='+id;
            for(var property in params){
                url+="&"+property+"="+params[property];
            }

            AjaxRequest.displayBox(Contao.lang.loading + ' …');

            fetch(url,{
                method:'get',
                redirect:'manual'
            })
            .then(response => {
                if(!response.ok){
                    response.json().then(function(json) {
                        alert(json.message);
                    });
                }
                AjaxRequest.hideBox();
            })
            .catch(error => {
                alert(error.message);
                AjaxRequest.hideBox();
            });

            return false;
        }
    }
    var self = WEM.Grid.Saver;
})();
var WEM = WEM || {};
WEM.Grid  = WEM.Grid || {};
(function() {
    WEM.Grid.Utils = WEM.Grid.Utils || {
        modal : null,
        /**
         * Override of Backend.openModalIframe to allow onShow & onHide callbacks
         * Open an iframe in a modal window
         *
         * @param {object} options An optional options object
         */
         openModalIframe:function(options) {
            var opt = options || {},
                maxWidth = (window.getSize().x - 20).toInt(),
                maxHeight = (window.getSize().y - 137).toInt();
            if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
            if (!opt.height || opt.height > maxHeight) opt.height = maxHeight;
            self.modal = new SimpleModal({
                'width': opt.width,
                'hideFooter': true,
                'draggable': false,
                'overlayOpacity': .7,
                'onShow': function() { document.body.setStyle('overflow', 'hidden');if("undefined" != typeof opt.onShow){opt.onShow();} },
                'onHide': function() { document.body.setStyle('overflow', 'auto');if("undefined" != typeof opt.onHide){opt.onHide();} }
            });
            self.modal.show({
                'title': opt.title,
                'contents': '<iframe src="' + opt.url + '" width="100%" onload="WEM.Grid.Utils.closeIfNotEdit(this);" height="' + opt.height + '" frameborder="0"></iframe>',
                'model': 'modal'
            });
        },
        closeIfNotEdit:function(iframe){
            if(-1 == iframe.contentWindow.location.search.indexOf('act=edit')){
                // close the modal ...
                self.modal.hide();
                self.modal = null;
            }
        }
    }
    var self = WEM.Grid.Utils;
})();
window.addEvent("domready", function () {
    WEM.Grid.Drag.init();
    // const regexpBreakpoints = /(-xxs|-xs|-sm|-md|-lg|-xl)/;
    
    document.querySelectorAll('.gridelement .be_item_grid').forEach(function (item) {
        // Retrieve value of select and input
        var classes = [];
        item.querySelectorAll('select').forEach(function(select){
            classes.push(select.value);
        });

        if(item.querySelector('input')) {
            classes.push(item.querySelector('input').value);
        }
        
        var c = item.getAttribute('class');
        for(var i in classes) {
            c = c.replace(classes[i], "");
        }

        item.setAttribute('data-class', c.trim().replace('hidden','wem_hidden'));
    });

    document.querySelectorAll('.gridelement select[data-type="cols"]').forEach(function (i) {
        i.addEventListener("change", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,i.getAttribute('data-breakpoint'));
            WEM.Grid.Saver.saveItemCols(itemGrid.getAttribute('data-id'),i.value,i.getAttribute('data-breakpoint'));
            // update lower resolution values
            WEM.Grid.Drag.changeLowerResolutionValues(
                i.getAttribute('data-item-id'),
                i.getAttribute('data-type'),
                i.getAttribute('data-breakpoint'),
                WEM.Grid.Drag.getNbColumnsOrRowsFromCssClass(i.value)
            );
        });
        i.addEventListener("change_auto", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,i.getAttribute('data-breakpoint'));
            // update lower resolution values
            WEM.Grid.Drag.changeLowerResolutionValues(
                i.getAttribute('data-item-id'),
                i.getAttribute('data-type'),
                i.getAttribute('data-breakpoint'),
                WEM.Grid.Drag.getNbColumnsOrRowsFromCssClass(i.value)
            );
        });
    });

    document.querySelectorAll('.gridelement select[data-type="rows"]').forEach(function (i) {
        i.addEventListener("change", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,i.getAttribute('data-breakpoint'));
            WEM.Grid.Saver.saveItemRows(itemGrid.getAttribute('data-id'),i.value,i.getAttribute('data-breakpoint'));
            // update lower resolution values
            WEM.Grid.Drag.changeLowerResolutionValues(
                i.getAttribute('data-item-id'),
                i.getAttribute('data-type'),
                i.getAttribute('data-breakpoint'),
                WEM.Grid.Drag.getNbColumnsOrRowsFromCssClass(i.value)
            );
        });
        i.addEventListener("change_auto", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,i.getAttribute('data-breakpoint'));
            // update lower resolution values
            var select = WEM.Grid.Drag.getSelectForItemIdAndTypeAndBreakpoint(
                i.getAttribute('data-item-id'),
                i.getAttribute('data-type'),
                i.getAttribute('data-breakpoint')
            );

            WEM.Grid.Drag.changeLowerResolutionValues(
                select.getAttribute('data-item-id'),
                select.getAttribute('data-type'),
                select.getAttribute('data-breakpoint'),
                WEM.Grid.Drag.getNbColumnsOrRowsFromCssClass(select.value)
            );
        });
    });

    document.querySelectorAll('.gridelement input').forEach(function (i) {
        i.addEventListener("change", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,this.parentNode.querySelector('select[data-type="rows"][data-item-id="'+itemGrid.getAttribute('data-id')+'"]:not(.hidden)').getAttribute('data-breakpoint'));
            WEM.Grid.Saver.saveItemClass(itemGrid.getAttribute('data-id'),i.value);
        });
        i.addEventListener("keyup_auto", function (e) {
            var itemGrid = WEM.Grid.Drag.getParentGridItemElement(this);
            WEM.Grid.Drag.updateItemDataClass(itemGrid,this.parentNode.querySelector('select[data-type="rows"][data-item-id="'+itemGrid.getAttribute('data-id')+'"]:not(.hidden)').getAttribute('data-breakpoint'));
        });
    });

    document.querySelectorAll('.be_item_grid > .item-new').forEach(function (container){
        var lastElement = WEM.Grid.Drag.getGridLastRealElement(container);
        if(null == lastElement){
            lastElement = WEM.Grid.Drag.getGridFromElement(container);
        }
        container.addEventListener("click", function (e) {
            e.preventDefault();
            WEM.Grid.Utils.openModalIframe({
                title:WEM.Grid.Translations.new
                ,url:window.location.href.replace('act=edit','act=create').replace(/\&id=([0-9]+)/,'&pid='+lastElement.getAttribute('data-id'))+'&popup=1&nc=1'
                ,onHide:function(){
                    window.location.reload();
                }
            });
        });
    });

    document.querySelectorAll('.item-copy').forEach(function (a){
        a.addEventListener('click', function(e){
            e.preventDefault();
            var target = e.target;
            while('A' != target.nodeName){
                target = target.parentNode;
            }
            var id = target.getAttribute('data-element-id');

            var urlCopy = window.location.href.replace('act=edit','act=paste&mode=copy').replace(/\&id=([0-9]+)/,'&id='+id);
            var urlPaste = window.location.href.replace('act=edit','act=copy&mode=1').replace(/\&id=([0-9]+)/,'&id='+id+'&pid='+id);

            AjaxRequest.displayBox(Contao.lang.loading + ' …');
            fetch(urlCopy,{
                method:'get',
                redirect:'manual'
            })
            .then(data => {
                fetch(urlPaste,{
                    method:'get',
                    redirect:'manual'
                })
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    AjaxRequest.hideBox();
                });
            })
            .catch(error => {
                AjaxRequest.hideBox();
            });


            return false;
        });
    });

    document.querySelectorAll('.item-delete').forEach(function (a){
        a.addEventListener('click', function(e){
            e.preventDefault();
            var target = e.target;
            while('A' != target.nodeName){
                target = target.parentNode;
            }
            var id = target.getAttribute('data-element-id');

            var url = window.location.href.replace('act=edit','act=delete').replace(/\&id=([0-9]+)/,'&id='+id);

            AjaxRequest.displayBox(Contao.lang.loading + ' …');

            fetch(url,{
                method:'get',
                redirect:'manual'
            })
            .then(data => {
                window.location.reload();
            })
            .catch(error => {
                AjaxRequest.hideBox();
            });

            return false;
        });
    });

    for(var i =0; i<= 6; i++){
        var select = document.querySelector('[name="grid_cols['+i+'][value]"]');
        if(null !== select){
            select.addEventListener('change',function(event){
                var nbColumns = parseInt(event.target.value);
                if('' === event.target.value){
                    var resolution = WEM.Grid.Drag.getClosestHigherResolutionDefinedForGrid(event.target.getAttribute('data-breakpoint'));
                    var selectDefined = WEM.Grid.Drag.getSelectForBreakpointForGrid(resolution);
                    nbColumns = parseInt(selectDefined.value);
                }
                if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
                    return;
                }
                // Update the main grid size
                WEM.Grid.Drag.updateMainGridNbOfColumns(nbColumns);
                // Update the fake elements size
                WEM.Grid.Drag.updateMainGridFakeElementsNbOfColumns(nbColumns);

                WEM.Grid.Drag.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), document.querySelector(WEM.Grid.Drag.selectors.gridMode).value, event.target.getAttribute('data-breakpoint'), nbColumns);
            });
        }
    }
    if(WEM.Grid.Drag.gridMode.automatic === document.querySelector(WEM.Grid.Drag.selectors.gridMode).value){
        WEM.Grid.Drag.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), document.querySelector(WEM.Grid.Drag.selectors.gridMode).value, 'all', '12'); // last 2 parameters do not matter
    }

    var selectBreakpoints = document.querySelector(WEM.Grid.Drag.selectors.breakpointSelector);
    if(null != selectBreakpoints){
        selectBreakpoints.addEventListener('change',function(event){
            WEM.Grid.Drag.updateGridElementsSelectNbColumnsVisibility(event.target.value);
            var select = document.querySelector('select[data-breakpoint="'+event.target.value+'"]');
            if(null != select){
                select.dispatchEvent(new Event('change'));
            }
        });
    }else{
        if(WEM.Grid.Drag.gridMode.automatic === document.querySelector(WEM.Grid.Drag.selectors.gridMode).value){
            WEM.Grid.Drag.updateGridElementsSelectNbColumnsVisibility('all');
        }
    }

    var selectGridGapValue = document.querySelector(WEM.Grid.Drag.selectors.gridGapValue);
    if(null != selectGridGapValue){
        selectGridGapValue.addEventListener('change',function(event){
            var selectGridGapUnit= document.querySelector(WEM.Grid.Drag.selectors.gridGapUnit);
            if(null != selectGridGapUnit){
                WEM.Grid.Drag.updateMainGridGap(event.target.value, selectGridGapUnit.value);
            }
        });
    }

    var selectGridGapUnit = document.querySelector(WEM.Grid.Drag.selectors.gridGapUnit);
    if(null != selectGridGapUnit){
        selectGridGapUnit.addEventListener('change',function(event){
            var selectGridGapValue= document.querySelector(WEM.Grid.Drag.selectors.gridGapValue);
            if(null != selectGridGapValue){
                WEM.Grid.Drag.updateMainGridGap(selectGridGapValue.value, event.target.value);
            }
        });
    }
});