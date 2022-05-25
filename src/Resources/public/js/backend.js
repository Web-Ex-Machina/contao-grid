window.addEvent("domready", function () {
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
        var lastElement = getGridLastRealElement();
        container.addEventListener("click", function (e) {
            e.preventDefault();
            Backend.openModalIframe({
                // width:w
                title:'Nouvel élément'
                // ,url:window.location.href.replace('act=edit','act=create').replace(/\&id=([0-9]+)/,'&pid=$1')+'&popup=1&nb=1'
                ,url:window.location.href.replace('act=edit','act=create').replace(/\&id=([0-9]+)/,'&pid='+lastElement.getAttribute('data-id'))+'&popup=1&nb=1'
            });
        });
    });
    console.clear();
    document.querySelectorAll('.grid_preview > .be_item_grid').forEach(function (container){ // only first level elements, not nested ones
        if("false" !== container.getAttribute('draggable')){
            // container.setAttribute('draggable',true);
            // container.addEventListener('dragstart',gridItemOnDragStart);
            // container.addEventListener('dragend',gridItemOnDragEnd);
            // container.addEventListener('dragover',gridItemOnDragOver);

            var dragBtn = container.querySelector('.drag-handle');
            if(null !== dragBtn){
                dragBtn.addEventListener('dragstart',gridItemOnDragStart);
                dragBtn.addEventListener('dragend',gridItemOnDragEnd);
                dragBtn.addEventListener('dragover',gridItemOnDragOver);
            }
        }

        if("false" !== container.getAttribute('dropable')){
            container.setAttribute('dropable',true);
            container.addEventListener('dragover',gridItemOnDragOver);
            container.addEventListener('dragenter',gridItemOnDragEnter);
            container.addEventListener('dragleave',gridItemOnDragLeave);
            container.addEventListener('drop',gridItemOnDrop);
        }
        // save the original number of columns
        for(i = 1; i <=12;i++){
            if(-1 < container.className.indexOf('cols-span-'+i)){
                container.setAttribute('data-cols-span',i);
            }
        }
    });

    document.querySelector('[name="grid_cols[0][value]"]').addEventListener('keyup',function(event){
        var nbColumns = parseInt(event.target.value);
        if(isNaN(nbColumns) || 12 < nbColumns || 0 >= nbColumns){
            return;
        }
        // Update the main grid size
        var grid = document.querySelector('.grid_preview');
        grid.className = grid.className.replace(/cols-([0-9]{1,2})/,'cols-'+nbColumns);
        // Update the fake elements size
        document.querySelectorAll('.grid_preview .be_item_grid_fake').forEach(function(item){
            item.className = item.className.replace(/cols-span-([0-9]{1,2})/,'cols-span-'+nbColumns);
        });
        // Update the items' available size options
        document.querySelectorAll('.grid_preview > .be_item_grid').forEach(function(item){
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
                select.add(new Option(i+' colonne(s)','cols-span-'+i,false,parseInt(item.getAttribute('data-cols-span')) == i ? true : false));
            }

            for(i = 1; i <= 12; i++){
                if(-1 < item.className.indexOf('cols-span-'+i) && i > nbColumns){
                    item.classList.toggle('cols-span-'+i);
                }
            }

            select.dispatchEvent(new Event('change'));
        });
    });

    function gridItemOnDragStart(event){
        var target = event.target.parentNode.parentNode;
        event
            .dataTransfer
            .setData('text/plain', target.getAttribute('data-id'));
        target.classList.toggle('drag-start');
        // coordinates stuff
        var rect = target.getBoundingClientRect();
        var real = window.getComputedStyle(target);
        target.setAttribute('data-mouse-offset-x',event.clientX - rect.left); //x position within the element.
        target.setAttribute('data-mouse-offset-y',event.clientY - rect.top);  //y position within the element.
        target.setAttribute('data-offset-x',parseFloat(real.left));
        target.setAttribute('data-offset-y',parseFloat(real.top));
        document.body.addEventListener('mousemove',gridItemDragging);
        document.body.addEventListener('mouseup',function(){
            // document.body.removeEventListener('mousemove',gridItemDragging,false);
            document.body.removeEventListener('mousemove',gridItemDragging);
        });
    }

    function gridItemOnDragEnd(event){
        var target = event.target.parentNode.parentNode;
        target.classList.toggle('drag-start','');
        target.setAttribute('data-mouse-offset-x',false);
        target.setAttribute('data-mouse-offset-y',false);
        target.setAttribute('data-offset-x',false);
        target.setAttribute('data-offset-y',false);
    }

    function gridItemOnDragOver(event){
        event.preventDefault();
        // var target = event.target.parentNode.parentNode;
        // target.style.left = parseInt(target.getAttribute('data-offset-x')) + event.clientX - parseInt(target.getAttribute('data-mouse-offset-x')) + 'px';
        // target.style.top = parseInt(target.getAttribute('data-offset-y')) + event.clientY - parseInt(target.getAttribute('data-mouse-offset-y')) + 'px';
    }

    function gridItemDragging(event){
        console.log('move');
        event.preventDefault();
        if(event.dataTransfer){
            var target = document.querySelector('[data-id="'+event.dataTransfer.getData('text')+'"]');
            target.style.left = parseInt(target.getAttribute('data-offset-x')) + event.clientX - parseInt(target.getAttribute('data-mouse-offset-x')) + 'px';
            target.style.top = parseInt(target.getAttribute('data-offset-y')) + event.clientY - parseInt(target.getAttribute('data-mouse-offset-y')) + 'px';
        }
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
            pid = getGridLastRealElement().getAttribute('data-id');
            doDoublePositionning = false;
        }else if('fake-first-element' == dropzone.getAttribute('data-type')){
            var dropzone = getGridFirstRealElement();
            pid = dropzone.getAttribute('data-id');
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

    function getGridFirstRealElement(){
        var grid = document.querySelector('.grid_preview');

        elements = grid.querySelectorAll('[data-type]');

        var elementIndex = 0;
        var element = elements[elementIndex];
        while(-1 < element.getAttribute('data-type').indexOf('fake-')){
            elementIndex++;
            element = elements[elementIndex];
        }

        return element;
    }

    function getGridLastRealElement(){
        var grid = document.querySelector('.grid_preview');

        elements = grid.querySelectorAll('[data-type]');

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