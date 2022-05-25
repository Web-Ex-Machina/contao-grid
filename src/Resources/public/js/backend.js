window.addEvent("domready", function () {
    document.querySelector('.gridelement .helpers .grid_toggleHelpers').addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelectorAll('.gridelement .grid_preview .be_item_grid').forEach(function (i) {
            i.classList.toggle('helper');
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
        container.addEventListener("click", function (e) {
            e.preventDefault();
            Backend.openModalIframe({
                // width:w
                title:'Nouvel élément'
                ,url:window.location.href.replace('act=edit','act=create').replace(/\&id=([0-9]+)/,'&pid=$1')+'&popup=1&nb=1'
            });
        });
    });

    document.querySelectorAll('.grid_preview > .be_item_grid').forEach(function (container){ // only first level elements, not nested ones
        if("false" !== container.getAttribute('draggable')){
            container.setAttribute('draggable',true);
            container.addEventListener('dragstart',gridItemOnDragStart);
            container.addEventListener('dragend',gridItemOnDragEnd);
            container.addEventListener('dragover',gridItemOnDragOver);
        }
        if("false" !== container.getAttribute('dropable')){
            container.setAttribute('dropable',true);
            container.addEventListener('dragover',gridItemOnDragOver);
            container.addEventListener('dragenter',gridItemOnDragEnter);
            container.addEventListener('dragleave',gridItemOnDragLeave);
            container.addEventListener('drop',gridItemOnDrop);
        }
    });

    function gridItemOnDragStart(event){
        // event.preventDefault();
        event
            .dataTransfer
            .setData('text/plain', event.target.getAttribute('data-id'));
        // event.target.className = event.target.className.concat('drag-start');
        event.target.classList.toggle('drag-start');
    }

    function gridItemOnDragEnd(event){
        event.target.classList.toggle('drag-start','');
    }

    function gridItemOnDragOver(event){
        event.preventDefault();
    }

    function gridItemOnDragEnter(event){
        if(!event.target.getAttribute('dropable')){
            return;
        }
        event.target.classList.toggle('drag-enter');
    }

    function gridItemOnDragLeave(event){
        if(!event.target.getAttribute('dropable')){
            return;
        }
        event.target.classList.toggle('drag-enter','');
    }

    function gridItemOnDrop(event){
        event.preventDefault();
        
        var dropzone = event.target;
        var id = event
            .dataTransfer
            .getData('text');
        var draggableElement = document.querySelector('[data-id="'+id+'"]');
        var pid = dropzone.getAttribute('data-id');
        var grid = document.querySelector('.grid_preview');

        event.target.classList.toggle('drag-enter','');
        draggableElement.classList.toggle('drag-enter','');

        if(!dropzone.getAttribute('dropable')
        || id == pid
        ){
            return;
        }

        var requests = [];
        var doDoublePositionning = true;

        if('fake-last-element' == dropzone.getAttribute('data-type')){
            var realdropzone = dropzone.previousSibling;
            if('grid-start' == realdropzone.getAttribute('data-type')){
                // if we drag over a grid, place the element after the corresponding grid-stop
                var gridStops = realdropzone.querySelectorAll('[data-type="grid-stop"]');
                pid = gridStops[gridStops.length-1].getAttribute('data-id');
            }else{
                pid = realdropzone.getAttribute('data-id');
            }
            doDoublePositionning = false;
        }else if('fake-first-element' == dropzone.getAttribute('data-type')){
            var dropzone = dropzone.nextSibling;
            pid = dropzone.getAttribute('data-id');
        // }else if('grid-start' == dropzone.getAttribute('data-type')){
            // if we drag over a grid, place the element after the corresponding grid-stop
            // var gridStops = dropzone.querySelectorAll('[data-type="grid-stop"]');
            // pid = gridStops[gridStops.length-1].getAttribute('data-id');
        } 

        if('grid-start' == draggableElement.getAttribute('data-type')){
            // if we move a grid-start, we have to move all children elements before the dropzone
            // move the grid start
            requests.push(getContaoRequestPutElementAfterAnother(id, pid));
            // move the grid elements
            pid = id; // the grid start becomes the PID
            var gridElements = draggableElement.querySelectorAll('[data-type]');
            gridElements.forEach(function(gridElement){
                id = gridElement.getAttribute('data-id');
                requests.push(getContaoRequestPutElementAfterAnother(id, pid));
                pid = id; // grid elements stay behind each others
            });
            if(doDoublePositionning){
                requests.push(getContaoRequestPutElementAfterAnother(dropzone.getAttribute('data-id'), pid));
            }
        }else{
            requests.push(getContaoRequestPutElementAfterAnother(id, pid));
            if(doDoublePositionning){
                requests.push(getContaoRequestPutElementAfterAnother(pid, id));
            }
        }
        runFakeQueue(requests);

        // once done, exchange both element places in display
        grid.removeChild(draggableElement);
        grid.insertBefore(draggableElement,dropzone);
    }

    function getContaoRequestPutElementAfterAnother(id, pid, params = {}){
        var req,href;
        req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=cut&mode=1&pid=' + pid;
        href = window.location.href.replace(/\?.*$/, '');
        params = Object.assign(params, {'url':href + req, 'followRedirects':false});
        return params;
    }

    function runFakeQueue(requests){
        if(requests.length <= 0){
            return;
        }
        AjaxRequest.displayBox(Contao.lang.loading + ' …');
        runFakeQueueItem(requests,0);
    }

    function runFakeQueueItem(requests, index){
        fetch(requests[index].url,{
            method:'get',
            redirect:'manual'
        })
        .then(data => {
            if("undefined" != typeof requests[index+1]){
                runFakeQueueItem(requests,index+1);
            }else{
                AjaxRequest.hideBox();
            }
        })
        .catch(error => {
            AjaxRequest.hideBox();
        });
    }

});