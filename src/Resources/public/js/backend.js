var WEM = WEM || {};
WEM.Grid  = WEM.Grid || {};
(function() {
    WEM.Grid.Drag = WEM.Grid.Drag || {
        selectors:{
            grid:'.grid_preview',
            firstLevelElements:'.grid_preview > .be_item_grid',
            allLevelElements:'.grid_preview .be_item_grid',
            breakpointSelector:'select[name="ctrl_select_breakpoints_"]',
            gridGapValue:'select[name="grid_gap[value]"]',
            gridGapUnit:'select[name="grid_gap[unit]"]',
        },
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
                var breakpoints = ['all','xxs','xs','sm','md','lg','xl'];
                for(var index in breakpoints){
                    var breakpoint = breakpoints[index];
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
            console.clear();
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
                gridDest.insertBefore(draggableElement,dropzone);
            }else{
                gridDest.insertBefore(draggableElement,dropzone.nextSibling);

            }

            var currentBreakpoint = document.querySelector(self.selectors.breakpointSelector).value;
            var inputNbCols = self.getInputNumberOfColumnsForBreakpoint(currentBreakpoint);

            self.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), inputNbCols.getAttribute('data-breakpoint'), inputNbCols.value);
        }
        ,getInputNumberOfColumnsForBreakpoint(breakpoint){
            for(var i = 0; i<=6;i++){
                var input = document.querySelector('[name="grid_cols['+i+'][value]"]');
                if(breakpoint == input.getAttribute('data-breakpoint')){
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
                // if we drag over a grid, place the element after the corresponding grid-stop
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
        ,updateGridElementsAvailableColumns:function(grid, breakpoint, nbColumns){
            nbColumns = parseInt(nbColumns);
            if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
                return;
            }
            // Update the items' available size options
            grid.querySelectorAll(':scope > .be_item_grid').forEach(function(item){
                if("grid-start" === item.getAttribute('data-type')){
                    self.updateGridElementsAvailableColumns(item.querySelector('.ce_grid-start'), breakpoint, item.getAttribute('data-nb-cols'));
                }
                var select = item.querySelector('select[name="grid_items['+item.getAttribute('data-id')+'_cols]['+breakpoint+']"]');
                var dataAttributeName='data-cols-span'+('all' == breakpoint ? '' : '-'+breakpoint);
                var classNameBase='cols-span'+('all' == breakpoint ? '' : '-'+breakpoint)+'-';

                if(null === select){
                    return;
                }
                // remove all options
                var length = select.options.length;
                for(i = 0; i <= length; i++){
                    select.remove(0);
                }
                // recreate options
                select.add(new Option('-','',false,null == item.getAttribute(dataAttributeName) ? true : false));
                for(var i = 1; i <= nbColumns; i++){
                    select.add(new Option(WEM.Grid.Translations.columns[i-1],classNameBase+i,false,parseInt(item.getAttribute(dataAttributeName)) == i ? true : false));
                }

                for(i = 1; i <= 12; i++){
                    if(-1 < item.className.indexOf(classNameBase+i) && i > nbColumns){
                        item.classList.toggle(classNameBase+i,true);
                    }
                }

                select.dispatchEvent(new Event('change_auto'));
            });
        }
    }
    var self = WEM.Grid.Drag;
})();

window.addEvent("domready", function () {
    WEM.Grid.Drag.init();
    const regexpBreakpoints = /(-xxs|-xs|-sm|-md|-lg|-xl)/;
    document.querySelector('.gridelement .helpers .grid_toggleHelpers').addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelectorAll('.gridelement .grid_preview .be_item_grid').forEach(function (i) {
            i.classList.toggle('helper');
            i.classList.toggle('fake-helper');
        });
    });

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

        item.setAttribute('data-class', c.trim());
    });

    document.querySelectorAll('.gridelement select[data-type="cols"]').forEach(function (i) {
        i.addEventListener("change", function (e) {
            updateItemDataClass(this.parentNode.parentNode.parentNode.parentNode,i.getAttribute('data-breakpoint'));
            saveItemCols(this.parentNode.parentNode.parentNode.parentNode.getAttribute('data-id'),i.value,i.getAttribute('data-breakpoint'));
        });
        i.addEventListener("change_auto", function (e) {
            updateItemDataClass(this.parentNode.parentNode.parentNode.parentNode,i.getAttribute('data-breakpoint'));
        });
    });

    document.querySelectorAll('.gridelement select[data-type="rows"]').forEach(function (i) {
        i.addEventListener("change", function (e) {
            updateItemDataClass(this.parentNode.parentNode.parentNode.parentNode,i.getAttribute('data-breakpoint'));
            saveItemRows(this.parentNode.parentNode.parentNode.parentNode.getAttribute('data-id'),i.value,i.getAttribute('data-breakpoint'));
        });
        i.addEventListener("change_auto", function (e) {
            updateItemDataClass(this.parentNode.parentNode.parentNode.parentNode,i.getAttribute('data-breakpoint'));
        });
    });

    document.querySelectorAll('.gridelement input').forEach(function (i) {
        i.addEventListener("change", function (e) {
            updateItemDataClass(this.parentNode.parentNode,this.parentNode.querySelector('select[data-type="rows"][data-item-id="'+this.parentNode.parentNode.getAttribute('data-id')+'"]:not(.hidden)').getAttribute('data-breakpoint'));
            saveItemClass(this.parentNode.parentNode.getAttribute('data-id'),i.value);
        });
        i.addEventListener("keyup_auto", function (e) {
            updateItemDataClass(this.parentNode.parentNode,this.parentNode.querySelector('select[data-type="rows"][data-item-id="'+this.parentNode.parentNode.getAttribute('data-id')+'"]:not(.hidden)').getAttribute('data-breakpoint'));
        });
    });

    document.querySelectorAll('.gridelement .helpers .grid_toggleBreakPoint').forEach(function (i) {
        i.addEventListener("click", function (e) {
            e.preventDefault();
            var w = '100%';
            var title = "Grid Preview : ";
            switch (e.target.getAttribute('data-breakpoint')) {
                case 'xxs': w = '400px'; bounds = "XXS (From 0px to 520px)"; break;
                case 'xs': w = '600px'; bounds = "XS (From 521px to 620px)"; break;
                case 'sm': w = '768px'; bounds = "SM (From 621px to 768px)"; break;
                case 'md': w = '992px'; bounds = "MD (From 769px to 992px)"; break;
                case 'lg': w = '1200px'; bounds = "LG (From 993px to 1200px)"; break;
                case 'xl': w = '1400px'; bounds = "XL (From 1201px to 1400px)"; break;
                default: w = '100%';
            }

            Backend.openModalIframe({
                width:w
                ,title:title+bounds
                ,url:window.location.href+'&grid_preview=1'
            });
        });
    });

    document.querySelectorAll('.be_item_grid > .item-new').forEach(function (container){
        var lastElement = WEM.Grid.Drag.getGridLastRealElement(container);
        if(null == lastElement){
            lastElement = WEM.Grid.Drag.getGridFromElement(container);
        }
        container.addEventListener("click", function (e) {
            e.preventDefault();
            openModalIframe({
                title:WEM.Grid.Translations.new
                ,url:window.location.href.replace('act=edit','act=create').replace(/\&id=([0-9]+)/,'&pid='+lastElement.getAttribute('data-id'))+'&popup=1&nb=1'
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
        var input = document.querySelector('[name="grid_cols['+i+'][value]"]');
        if(null !== input){
            input.addEventListener('keyup',function(event){
                var nbColumns = parseInt(event.target.value);
                if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
                    return;
                }
                // Update the main grid size
                updateMainGridNbOfColumns(nbColumns);
                // Update the fake elements size
                updateMainGridFakeElementsNbOfColumns(nbColumns);

                WEM.Grid.Drag.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), event.target.getAttribute('data-breakpoint'), nbColumns);
            });
        }
    }

    var selectBreakpoints = document.querySelector(WEM.Grid.Drag.selectors.breakpointSelector);
    if(null != selectBreakpoints){
        selectBreakpoints.addEventListener('change',function(event){
            updateGridElementsSelectNbColumnsVisibility(event.target.value);
            var input = document.querySelector('input[data-breakpoint="'+event.target.value+'"]');
            if(null != input){
                input.dispatchEvent(new Event('keyup_auto'));
            }
        });
    }

    var selectGridGapValue = document.querySelector(WEM.Grid.Drag.selectors.gridGapValue);
    if(null != selectGridGapValue){
        selectGridGapValue.addEventListener('change',function(event){
            var selectGridGapUnit= document.querySelector(WEM.Grid.Drag.selectors.gridGapUnit);
            if(null != selectGridGapUnit){
                updateMainGridGap(event.target.value, selectGridGapUnit.value);
            }
        });
    }

    var selectGridGapUnit = document.querySelector(WEM.Grid.Drag.selectors.gridGapUnit);
    if(null != selectGridGapUnit){
        selectGridGapUnit.addEventListener('change',function(event){
            var selectGridGapValue= document.querySelector(WEM.Grid.Drag.selectors.gridGapValue);
            if(null != selectGridGapValue){
                updateMainGridGap(selectGridGapValue.value, event.target.value);
            }
        });
    }

    function updateMainGridGap(gapValue,gapUnit){
        var grid = document.querySelector(WEM.Grid.Drag.selectors.grid);
        if(-1 < grid.className.indexOf('gap')){
            grid.className = grid.className.replace(/gap-([0-6]{1})([-rem]{0,4})/,'gap-'+gapValue+('' != gapUnit ? '-'+gapUnit : ''));
        }else{
            grid.className = grid.className.concat('gap-'+gapValue+('' != gapUnit ? '-'+gapUnit : ''));
        }
    }

    function updateMainGridNbOfColumns(nbColumns){
        var grid = document.querySelector(WEM.Grid.Drag.selectors.grid);
        grid.className = grid.className.replace(/cols-([0-9]{1,2})/,'cols-'+nbColumns);
    }

    function updateMainGridFakeElementsNbOfColumns(nbColumns){
        document.querySelectorAll(WEM.Grid.Drag.selectors.grid + ' > .be_item_grid_fake').forEach(function(item){
            item.className = item.className.replace(/cols-span-([0-9]{1,2})/,'cols-span-'+nbColumns);
        });
    }

    function updateGridElementsSelectNbColumnsVisibility(breakpoint){
        document.querySelectorAll(WEM.Grid.Drag.selectors.grid + ' select').forEach(function(item){
            if(null != item.getAttribute('data-breakpoint')){
                var shouldBeHidden = breakpoint != item.getAttribute('data-breakpoint') || "1" == item.getAttribute('data-force-hidden');
                item.classList.toggle('hidden', shouldBeHidden);
                document.querySelector('label[for="'+item.id+'"]').classList.toggle('hidden', shouldBeHidden);
                if(breakpoint == item.getAttribute('data-breakpoint')){
                    item.dispatchEvent(new Event('change_auto'));
                }
            }
        });
    }

    function updateItemDataClass(itemgrid, breakpoint){
        var strClass = itemgrid.querySelector('input[data-item-id="'+itemgrid.getAttribute('data-id')+'"]').value 
        + ' ' 
        + itemgrid.querySelector('select[data-type="rows"][data-item-id="'+itemgrid.getAttribute('data-id')+'"][data-breakpoint="'+breakpoint+'"]').value.replace(regexpBreakpoints,'') 
        + ' ' 
        + itemgrid.querySelector('select[data-type="cols"][data-item-id="'+itemgrid.getAttribute('data-id')+'"][data-breakpoint="'+breakpoint+'"]').value.replace(regexpBreakpoints,'');
        itemgrid.setAttribute('class', itemgrid.getAttribute('data-class')+' '+strClass);
    }

    function saveItemCols(itemId, value, breakpoint){
        return save({id: itemId, property: 'cols', value: value, breakpoint: breakpoint});
    }
    function saveItemRows(itemId, value, breakpoint){
        return save({id: itemId, property: 'rows', value: value, breakpoint: breakpoint});
    }
    function saveItemClass(itemId, value){
        return save({id: itemId, property: 'classes', value: value});
    }
    function save(params){
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

    /**
     * Override of Backend.openModalIframe to allow onShow & onHide callbacks
     * Open an iframe in a modal window
     *
     * @param {object} options An optional options object
     */
    function openModalIframe(options) {
        var opt = options || {},
            maxWidth = (window.getSize().x - 20).toInt(),
            maxHeight = (window.getSize().y - 137).toInt();
        if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
        if (!opt.height || opt.height > maxHeight) opt.height = maxHeight;
        var M = new SimpleModal({
            'width': opt.width,
            'hideFooter': true,
            'draggable': false,
            'overlayOpacity': .7,
            'onShow': function() { document.body.setStyle('overflow', 'hidden');if("undefined" != typeof opt.onShow){opt.onShow();} },
            'onHide': function() { document.body.setStyle('overflow', 'auto');if("undefined" != typeof opt.onHide){opt.onHide();} }
        });
        M.show({
            'title': opt.title,
            'contents': '<iframe src="' + opt.url + '" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
            'model': 'modal'
        });
    }
});