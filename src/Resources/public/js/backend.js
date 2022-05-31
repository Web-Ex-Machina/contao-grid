var WEM = WEM || {};
WEM.Grid  = WEM.Grid || {};
(function() {
    WEM.Grid.Drag = WEM.Grid.Drag || {
        selectors:{
            grid:'.grid_preview',
            firstLevelElements:'.grid_preview > .be_item_grid',
            allLevelElements:'.grid_preview .be_item_grid',
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
                for(i = 1; i <=12;i++){
                    if(-1 < container.className.indexOf('cols-span-'+i)){
                        container.setAttribute('data-cols-span',i);
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
                dropzone = self.getGridFirstRealElement(dropzone);
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
                var gridElements = draggableElement.querySelectorAll('[data-type]');
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

            self.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), document.querySelector('[name="grid_cols[0][value]"]').value);

        }
        ,getGridFirstRealElement:function(fromElement){
            // var grid = document.querySelector(self.selectors.grid);
            var grid = self.getGridFromElement(fromElement);

            var elements = grid.querySelectorAll('[data-type]');

            var elementIndex = 0;
            var element = elements[elementIndex];
            while(-1 < element.getAttribute('data-type').indexOf('fake-')){
                elementIndex++;
                element = elements[elementIndex];
            }

            return element;
        }
        ,getGridLastRealElement:function(fromElement){
            // var grid = document.querySelector(self.selectors.grid);
            var grid = self.getGridFromElement(fromElement);

            var elements = grid.querySelectorAll('[data-type]');

            var elementIndex = elements.length-1;
            var element = elements[elementIndex];
            while(-1 < element.getAttribute('data-type').indexOf('fake-')){
                elementIndex--;
                element = elements[elementIndex];
            }
            if('grid-start' == element.getAttribute('data-type')){
                // if we drag over a grid, place the element after the corresponding grid-stop
                var gridStops = element.querySelectorAll('[data-type="grid-stop"]');
                element = gridStops[gridStops.length-1];
            }

            return element;
        }
        ,getGridFromElement:function(element){
            if(-1 < element.className.indexOf('d-grid')
            && ( -1 < element.className.indexOf(self.selectors.grid.substring(1))
                || -1 < element.className.indexOf('ce_grid-start')
            )
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
        ,updateGridElementsAvailableColumns:function(grid, nbColumns){
            nbColumns = parseInt(nbColumns);
            if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
                return;
            }
            // Update the items' available size options
            grid.querySelectorAll(':scope > .be_item_grid').forEach(function(item){
                if("grid-start" === item.getAttribute('data-type')){
                    self.updateGridElementsAvailableColumns(item, item.getAttribute('data-nb-cols'));
                }

                var select = item.querySelector('select[name="grid_items['+item.getAttribute('data-id')+']"]');

                if(null === select){
                    return;
                }
                // remove all options
                var length = select.options.length;
                for(i = 0; i <= length; i++){
                    select.remove(0);
                }
                // recreate options
                select.add(new Option('-','',false,null == item.getAttribute('data-cols-span') ? true : false));
                for(var i = 1; i <= nbColumns; i++){
                    select.add(new Option(WEM.Grid.Translations.columns[i-1],'cols-span-'+i,false,parseInt(item.getAttribute('data-cols-span')) == i ? true : false));
                }

                for(i = 1; i <= 12; i++){
                    if(-1 < item.className.indexOf('cols-span-'+i) && i > nbColumns){
                        item.classList.toggle('cols-span-'+i,true);
                    }
                }

                select.dispatchEvent(new Event('change'));
            });
        }
    }
    var self = WEM.Grid.Drag;
})();

window.addEvent("domready", function () {
    WEM.Grid.Drag.init();
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

        if(item.querySelector('select')) {
            classes.push(item.querySelector('select').value);
        }
        if(item.querySelector('input')) {
            classes.push(item.querySelector('input').value);
        }
        
        var c = item.getAttribute('class');

        for(var i in classes) {
            c = c.replace(classes[i], "");
        }

        item.setAttribute('data-class', c.trim());
    });

    document.querySelectorAll('.gridelement select').forEach(function (i) {
        i.addEventListener("change", function (e) {
            var itemgrid = this.parentNode.parentNode;
            var strClass = this.value + ' ' + this.parentNode.querySelector('input').value;
            itemgrid.setAttribute('class', itemgrid.getAttribute('data-class')+' '+strClass);
        });
    });

    document.querySelectorAll('.gridelement input').forEach(function (i) {
        i.addEventListener("keyup", function (e) {
            var itemgrid = this.parentNode.parentNode;
            var strClass = this.value + ' ' + this.parentNode.querySelector('select').value;
            itemgrid.setAttribute('class', itemgrid.getAttribute('data-class')+' '+strClass);
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

    for(var i =0; i<= 6; i++){
        document.querySelector('[name="grid_cols['+i+'][value]"]').addEventListener('keyup',function(event){
            var nbColumns = parseInt(event.target.value);
            if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
                return;
            }
            // Update the main grid size
            updateMainGridNbOfColumns(nbColumns);
            // Update the fake elements size
            updateMainGridFakeElementsNbOfColumns(nbColumns);
            
            WEM.Grid.Drag.updateGridElementsAvailableColumns(document.querySelector(WEM.Grid.Drag.selectors.grid), nbColumns);
        });
    }
    document.querySelector('select[name="ctrl_select_breakpoints_"]').addEventListener('change',function(event){
        document.querySelector('input[data-breakpoint="'+event.target.value+'"]').dispatchEvent(new Event('keyup'));
    });
    document.querySelector('select[name="grid_gap[value]"]').addEventListener('change',function(event){
        updateMainGridGap(event.target.value, document.querySelector('select[name="grid_gap[unit]"]').value);
    });
    document.querySelector('select[name="grid_gap[unit]"]').addEventListener('change',function(event){
        updateMainGridGap(document.querySelector('select[name="grid_gap[value]"]').value, event.target.value);
    });

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