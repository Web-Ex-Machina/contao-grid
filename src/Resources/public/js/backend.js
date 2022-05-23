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
            alert('To do : open modal to create a new content element.');
        });
    });
});
