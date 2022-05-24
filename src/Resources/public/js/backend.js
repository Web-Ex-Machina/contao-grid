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

    document.querySelectorAll('.grid_preview > .be_item_grid').forEach(function (container){
        container.setAttribute('draggable',true);
        container.setAttribute('dropable',true);
        container.addEventListener('dragstart',gridItemOnDragStart);
        container.addEventListener('dragover',gridItemOnDragOver);
        container.addEventListener('drop',gridItemOnDrop);
    });

    function gridItemOnDragStart(event){
        // console.log('ondragstart',event.target);
        // event.preventDefault();
        event
            .dataTransfer
            .setData('text/plain', event.target.getAttribute('data-id'));
    }

    function gridItemOnDragOver(event){
        // console.log('ondragover',event.target);

        event.preventDefault();
    }

    function gridItemOnDrop(event){
        // console.log('ondrop',event.target);
        event.preventDefault();
        
        var dropzone = event.target;
        var id = event
            .dataTransfer
            .getData('text');
        var draggableElement = document.querySelector('[data-id="'+id+'"]');
        var pid = dropzone.getAttribute('data-id');
        var grid = document.querySelector('.grid_preview');

        if(!dropzone.getAttribute('dropable')
        || id == pid
        ){
            return;
        }

        if('grid-start' == dropzone.getAttribute('data-type')){
            // if we drag over a grid, place the element after the corresponding grid-stop
            var gridStops = dropzone.querySelectorAll('[data-type="grid-stop"]');
            pid = gridStops[gridStops.length-1].getAttribute('data-id');
            contaoPutElementAfterAnother(id, pid);
            // once done, exchange both element places in display
            grid.removeChild(draggableElement);
            grid.insertBefore(draggableElement,dropzone.nextSibling);
        }else if('grid-start' == draggableElement.getAttribute('data-type')){
            // if we move a grid-start, we have to move all child elements before the dropzone
            // move the grid start
            contaoPutElementAfterAnother(id, pid);
            // move the grid elements
            pid = id; // the grid start becomes the PID
            var gridElements = draggableElement.querySelectorAll('[data-type]');
            gridElements.forEach(function(gridElement){
                id = gridElement.getAttribute('data-id');
                contaoPutElementAfterAnother(id, pid);
                pid = id; // grid elements stay behind each others
            });
            // once done, exchange both element places in display
            grid.removeChild(draggableElement);
            grid.insertBefore(draggableElement,dropzone.nextSibling);
        }else{
            contaoPutElementAfterAnother(id, pid);
            // once done, exchange both element places in display
            grid.removeChild(draggableElement);
            grid.insertBefore(draggableElement,dropzone.nextSibling);
        }
    }

    function contaoPutElementAfterAnother(id, pid){
        // console.log(id + ' behind '+pid);
        var req,href;
        req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=cut&mode=1&pid=' + pid;
        href = window.location.href.replace(/\?.*$/, '');
        return new Request.Contao({'url':href + req, 'followRedirects':false}).get();
    }
});