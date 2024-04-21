### Done

 * ~~Tidy up css: include leaflet-control-toolbar for common styles~~
 * ~~Rename _shapes variable in Control.Draw to make better sense.~~
 * ~~Should the ext classes be renamed to Polyline.Intersect or similar?~~
 * ~~Make Control.Draw inherit from Control.Toolbar.~~
 * ~~Rename Handler.Draw -> Vector.Draw. What about markers? they aren't vectors, is there a better name? Maybe Feature?~~
 * ~~Add enbled/disabled states for the delete & edit buttons.~~
 * ~~Move control/handler files out of draw folder.~~
 * ~~Rename the draw events from draw:feature t0 feature-created.~~
 * ~~Revert to the correct colour for the feature that was just deselected.~~
 * ~~Rename the Handler activated/deactivated events to enabled/disabled.~~
 * ~~Add option for setting the selected color.~~
 * ~~Check and calls to L.Feature.Draw.prototype, are they correct? In Draw.Circle it hink it should be referencing L.Draw.SimpleShape~~
 * ~~Add in cancel buttons for selected button.~~
 * ~~Have special behavior for selected markers. Do we just set the background color?~~
 * ~~Turn the cancel button UI into a button container for things like undo.~~
 * ~~Add Save to edit mode. Same as cancel but does not revert any shapes.~~
 * ~~rename selectableLayers = layerGroup~~
 * ~~refactor the repositioning of the actions toolbar for Control.Draw.~~
 * ~~If more than 1 button in actions toolbar but not first is showing then margin is wrong.~~
 * ~~Support cancelling delete?~~
 * ~~Rename the _showCancel/_hideCancel methods in Control.Toolbar~~
 * ~~See if any common code can move to Control.Toolbar from Control.Draw.~~
 * ~~Fix the bottom border radius when the actions buttons are at the bottom~~
 * ~~Fix up the toolbar rounded corners when only 1 item in the toolbar.~~
 * ~~Handle layers being added/removed to the layergroup. i.e. need to be placed in edit mode or have a delete handler added~~
 * ~Add support for tooltips for the edit mode.~
 * ~Add handlers for Circle and Rectangle editing. (Needs a way to hook into L.Cicle and L.Rectangle)~
 * ~Fix styles to look more like new Leaflet zoom in/out.~
 * ~Polyline is styled as filled for edit mode.~
 * ~Add visual style change to toolbar buttons on mouse over.~
 * ~Add handlers to earch corner of the rectangle for resizing.~
 * ~Bug: if you go edit mode, then go to draw mode.~
 * ~Handle controls from being removed from map.~
 * ~Add link to http://glyphicons.com/~
 * ~Redo the select/delete icons.~
 * ~Merge the event change pull and add edit/delete versions.~
 * ~When switching from edit to delete and having edit a feature it should reset/cancel instead of saving.~
 * ~Move clone methods from Edit.Feature~
 * ~Renamed Edit.Feature -> Edit and Delete.Feature -> Delete, is confusing since Edit.feature is not the same as Edit.Circle etc~
 * ~Get Leaflet control-design branch merged to master.~
 * ~Fix action toolbar styles to match new toolbar height.~
 * ~Make Tooltip sexy!~
 * ~IE actions bar position.~
 * ~IE editable marker background and border.~
 * ~Search for TODO~
 * ~Update Deps. Maybe should make it more advanced to allow people to custom build without parts? Like edit only or draw only? Also file names ahve changed.~
 * ~Add some proper documentation. I.e. for the events & methods.~
 * ~Add a thanks section to README. Shramov, BrunboB, tnightingale & Glyphicons. Others?~
 * ~Write up a breaking changes for when 0.2 goes live. (See below)~
 * ~Add events to docs~
 * ~Fix the draw:enabled event. This is not used for the edit toolbar. It is simply used to state that drawing has started then ended.~
 * ~Custom build tool.~
 * ~Move Poly.Edit.js~
 * ~Add ability to update the options after control is initialized.~
 * ~Make a git tag of Leaflet.draw 0.1~
 * ~Document changing the options of a draw handler.~

### TODO

 * Fix all the Show Code links in the ReadMe.