Extension "Grid" for Contao Open Source CMS
========

1.0.8 - 2023-11-04
---
- UPDATED : compatibility with PHP 8.2
- UPDATED : compatibility with Contao 5.3

1.0.7 - 2023-10-18
---
- UPDATED: Remove contao-haste dependency since we do not use it

1.0.6 - 2023-10-16
---
- FIXED : subgrid element in back-end builder does not take its cols-span & rows-span configuration into account

1.0.5 - 2023-06-15
---
- UPDATED : PHP 8.2 compatibility

1.0.4 - 2023-06-15
---
- FIXED : the last "fake" grid element now takes whole grid width
- UPDATED : the "new" grid element is now taller
- UPDATED : cleaning CSS classes in BE grid preview

1.0.3 - 2023-05-22
---
- UPDATED : the fake "new element" is now inside the main grid
- FIXED : when moving an item from an "custom" to "automatic" grid (or vice-versa), columns & rows settings are now displayed/hidden accordingly


1.0.2 - 2023-05-12
---
- ADDED : automatic configuration mode for grid

1.0.1 - 2023-01-13
---
- REMOVED : reference to an unexisting CSS file

1.0.0 - 2022-12-14
---
- FIXED : do not reopen a grid if it is already opened

1.0.0-rc11 - 2022-11-14
---
- UPDATED : if a grid is already marked as open, do not reopen it.

1.0.0-rc10 - 2022-10-28
---
- UPDATED : BE - edit content modal now features the "Save & close" button
- UPDATED : BE - edit content modal now reload the grid after the modal is closed
- UPDATED : BE - new content modal now features the "Save & close" button

1.0.0-rc9 - 2022-10-19
---
- UPDATED : Adding a `grid-start` content element to an article will now trigger the creation of the corresponding `grid-stop` content element (and vice-versa)
- ADDED : Deleting a `grid-start` content element from an article will now trigger the deletion of the corresponding `grid-stop` content element (and vice-versa)
- ADDED : Restoring a `grid-start` content element from the trash bin will now trigger the restoration of the corresponding `grid-stop` content element (and vice-versa)

1.0.0-rc8 - 2022-06-17
---
- UPDATED : inherited values are displayed as "-"

1.0.0-rc7 - 2022-06-17
---
- REMOVED : inheritance text in number of columns & rows
- ADDED : ability to let the number of columns empty


1.0.0-rc6 - 2022-06-17
---
- UPDATED : rows displays "1 row" if it inerits from a breakpoint not defining one

1.0.0-rc5 - 2022-06-17
---
- ADDED : translations
- ADDED : new inheritance system
- UPDATED : root grid-start element get his CSS classes cleaned like its child elements

1.0.0-rc4 - 2022-06-17
---
- ADDED : empty element for grid
- ADDED : inheritance for cols & rows classes in grid builder
- REMOVED : grid_preset
- REMOVED : ability to get a preview in back-end
- REMOVED : ability to hide helpers in back-end

1.0.0-rc3 - 2022-06-17
---
- Markup cleaned
- Fix first fake grid element size updated with the grid size

1.0.0-rc2 - 2022-06-17
---
- Changing a grid element cols, rows or CSS classes settings automatically triggers an immediate save
- Applying the `hidden` CSS class to a grid element no longer hide it in back-end

1.0.0-rc1 - 2022-06-17
---
- Full rework for a better handling of grids in general

0.5 - 2022-05-30
---
- Visual builder when editing the grid-start content element

0.4 - 2020-03-23
---
- Use a new system to setup a grid with its breakpoints
- Use selects instead of inputs for item settings
- Add visual helpers in the list and in the grid element
- Remove the Rows settings for CSSGrid, still it doesn't really works as expected
- Remove temporarly the BS4 grid system (coming back planned for 0.5)
- Use translation system for labels
- Global fixing / stability

0.3 - 2019-02-27
---
- BS4 compatibility
- BS3 removed
- Nested grids
- Backend improvements

0.2 - 2019-02-19
---
- Grid preview in Backend

0.1 - 2019-01-26
---
- Init Repo