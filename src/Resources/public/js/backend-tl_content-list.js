window.addEvent("domready", function () {

	function replaceGrid(parentElement){
		parentElement.querySelectorAll('.d-grid').forEach(function (grid) {
			replaceItems(grid);
			replaceGrid(grid);
		});
	}

	function replaceItems(grid){
		grid.querySelectorAll('[data-item]').forEach(function(gridItem){
			replaceItem(gridItem);
		});
	}

	function replaceItem(gridItem){
		var li = document.querySelector('li#li_'+gridItem.getAttribute('data-item'));
		gridItem.innerHTML = li.innerHTML;
		li.innerHTML = null;
	}
	
	replaceGrid(document);
});