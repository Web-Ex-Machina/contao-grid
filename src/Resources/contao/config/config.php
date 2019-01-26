<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

// Add the Grid Wrapper Content Element
array_insert($GLOBALS['TL_CTE'], count($GLOBALS['TL_CTE'])+1, array(
	'grid' => array(
		'grid-start' => 'WEM\GridBundle\Elements\GridStart'
		,'grid-stop' => 'WEM\GridBundle\Elements\GridStop'
	)
));