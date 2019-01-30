window.addEvent("domready", function() {
	document.querySelector('.gridelement .helpers .grid_toggleHelpers').addEventListener("click", function(e){
		e.preventDefault();
		document.querySelectorAll('.gridelement .grid_preview .item-grid').forEach(function(i){
			i.classList.toggle('helper');
		});
	});
});