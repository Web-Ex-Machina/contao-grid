window.addEvent("domready", function() {
	document.querySelector('.gridelement .helpers .grid_toggleHelpers').addEventListener("click", function(e){
		e.preventDefault();
		document.querySelectorAll('.gridelement .grid_preview .item-grid').forEach(function(i){
			i.classList.toggle('helper');
		});
	});

	document.querySelectorAll('.gridelement .helpers .grid_toggleBreakPoint').forEach(function(i){
		i.addEventListener("click", function(e){
			e.preventDefault();
			var w = '100%';
			switch(e.target.getAttribute('data-breakpoint')){
				case 'xxs': w = '520px'; break;
				case 'xs': w = '620px'; break;
				case 'sm': w = '768px'; break;
				case 'md': w = '992px'; break;
				case 'lg': w = '1200px'; break;
				case 'xl': w = '1400px'; break;
				default: w = '100%';
			}

			Backend.openModalIframe({
				width:w
				,url:window.location.href+'&grid_preview=1'
			});
		});
	});
});