import * as i0 from '@angular/core';
import { Injectable, ViewContainerRef, Component, ViewChild, Input, EventEmitter, reflectComponentType, ContentChildren, Output, NgModule } from '@angular/core';
import { NgIf } from '@angular/common';
import { GridStack } from 'gridstack';

/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2025 Alain Dumesny - see GridStack root license
 */

/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
/**
 * Base widget class for GridStack Angular integration.
 */
class BaseWidget {
    /**
     * Override this method to return serializable data for this widget.
     *
     * Return an object with properties that map to your component's @Input() fields.
     * The selector is handled automatically, so only include component-specific data.
     *
     * @returns Object containing serializable component data
     *
     * @example
     * ```typescript
     * serialize() {
     *   return {
     *     title: this.title,
     *     value: this.value,
     *     settings: this.settings
     *   };
     * }
     * ```
     */
    serialize() { return; }
    /**
     * Override this method to handle widget restoration from saved data.
     *
     * Use this for complex initialization that goes beyond simple @Input() mapping.
     * The default implementation automatically assigns input data to component properties.
     *
     * @param w The saved widget data including input properties
     *
     * @example
     * ```typescript
     * deserialize(w: NgGridStackWidget) {
     *   super.deserialize(w); // Call parent for basic setup
     *
     *   // Custom initialization logic
     *   if (w.input?.complexData) {
     *     this.processComplexData(w.input.complexData);
     *   }
     * }
     * ```
     */
    deserialize(w) {
        // save full description for meta data
        this.widgetItem = w;
        if (!w)
            return;
        if (w.input)
            Object.assign(this, w.input);
    }
}
BaseWidget.ɵfac = i0.ɵɵngDeclareFactory({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: BaseWidget, deps: [], target: i0.ɵɵFactoryTarget.Injectable });
BaseWidget.ɵprov = i0.ɵɵngDeclareInjectable({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: BaseWidget });
i0.ɵɵngDeclareClassMetadata({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: BaseWidget, decorators: [{
            type: Injectable
        }] });

/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
/**
 * Angular component wrapper for individual GridStack items.
 *
 * This component represents a single grid item and handles:
 * - Dynamic content creation and management
 * - Integration with parent GridStack component
 * - Component lifecycle and cleanup
 * - Widget options and configuration
 *
 * Use in combination with GridstackComponent for the parent grid.
 *
 * @example
 * ```html
 * <gridstack>
 *   <gridstack-item [options]="{x: 0, y: 0, w: 2, h: 1}">
 *     <my-widget-component></my-widget-component>
 *   </gridstack-item>
 * </gridstack>
 * ```
 */
class GridstackItemComponent {
    constructor(elementRef) {
        this.elementRef = elementRef;
        this.el._gridItemComp = this;
    }
    /**
     * Grid item configuration options.
     * Defines position, size, and behavior of this grid item.
     *
     * @example
     * ```typescript
     * itemOptions: GridStackNode = {
     *   x: 0, y: 0, w: 2, h: 1,
     *   noResize: true,
     *   content: 'Item content'
     * };
     * ```
     */
    set options(val) {
        const grid = this.el.gridstackNode?.grid;
        if (grid) {
            // already built, do an update...
            grid.update(this.el, val);
        }
        else {
            // store our custom element in options so we can update it and not re-create a generic div!
            this._options = { ...val, el: this.el };
        }
    }
    /** return the latest grid options (from GS once built, otherwise initial values) */
    get options() {
        return this.el.gridstackNode || this._options || { el: this.el };
    }
    /** return the native element that contains grid specific fields as well */
    get el() { return this.elementRef.nativeElement; }
    /** clears the initial options now that we've built */
    clearOptions() {
        delete this._options;
    }
    ngOnDestroy() {
        this.clearOptions();
        delete this.childWidget;
        delete this.el._gridItemComp;
        delete this.container;
        delete this.ref;
    }
}
GridstackItemComponent.ɵfac = i0.ɵɵngDeclareFactory({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackItemComponent, deps: [{ token: i0.ElementRef }], target: i0.ɵɵFactoryTarget.Component });
GridstackItemComponent.ɵcmp = i0.ɵɵngDeclareComponent({ minVersion: "14.0.0", version: "14.3.0", type: GridstackItemComponent, isStandalone: true, selector: "gridstack-item", inputs: { options: "options" }, viewQueries: [{ propertyName: "container", first: true, predicate: ["container"], descendants: true, read: ViewContainerRef, static: true }], ngImport: i0, template: `
    <div class="grid-stack-item-content">
      <!-- where dynamic items go based on component selector (recommended way), or sub-grids, etc...) -->
      <ng-template #container></ng-template>
      <!-- any static (defined in DOM - not recommended) content goes here -->
      <ng-content></ng-content>
      <!-- fallback HTML content from GridStackWidget.content if used instead (not recommended) -->
      {{options.content}}
    </div>`, isInline: true, styles: [":host{display:block}\n"] });
i0.ɵɵngDeclareClassMetadata({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackItemComponent, decorators: [{
            type: Component,
            args: [{ selector: 'gridstack-item', template: `
    <div class="grid-stack-item-content">
      <!-- where dynamic items go based on component selector (recommended way), or sub-grids, etc...) -->
      <ng-template #container></ng-template>
      <!-- any static (defined in DOM - not recommended) content goes here -->
      <ng-content></ng-content>
      <!-- fallback HTML content from GridStackWidget.content if used instead (not recommended) -->
      {{options.content}}
    </div>`, standalone: true, styles: [":host{display:block}\n"] }]
        }], ctorParameters: function () { return [{ type: i0.ElementRef }]; }, propDecorators: { container: [{
                type: ViewChild,
                args: ['container', { read: ViewContainerRef, static: true }]
            }], options: [{
                type: Input
            }] } });

/**
 * gridstack.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
/**
 * Angular component wrapper for GridStack.
 *
 * This component provides Angular integration for GridStack grids, handling:
 * - Grid initialization and lifecycle
 * - Dynamic component creation and management
 * - Event binding and emission
 * - Integration with Angular change detection
 *
 * Use in combination with GridstackItemComponent for individual grid items.
 *
 * @example
 * ```html
 * <gridstack [options]="gridOptions" (change)="onGridChange($event)">
 *   <div empty-content>Drag widgets here</div>
 * </gridstack>
 * ```
 */
class GridstackComponent {
    constructor(elementRef) {
        this.elementRef = elementRef;
        /**
         * GridStack event emitters for Angular integration.
         *
         * These provide Angular-style event handling for GridStack events.
         * Alternatively, use `this.grid.on('event1 event2', callback)` for multiple events.
         *
         * Note: 'CB' suffix prevents conflicts with native DOM events.
         *
         * @example
         * ```html
         * <gridstack (changeCB)="onGridChange($event)" (droppedCB)="onItemDropped($event)">
         * </gridstack>
         * ```
         */
        /** Emitted when widgets are added to the grid */
        this.addedCB = new EventEmitter();
        /** Emitted when grid layout changes */
        this.changeCB = new EventEmitter();
        /** Emitted when grid is disabled */
        this.disableCB = new EventEmitter();
        /** Emitted during widget drag operations */
        this.dragCB = new EventEmitter();
        /** Emitted when widget drag starts */
        this.dragStartCB = new EventEmitter();
        /** Emitted when widget drag stops */
        this.dragStopCB = new EventEmitter();
        /** Emitted when widget is dropped */
        this.droppedCB = new EventEmitter();
        /** Emitted when grid is enabled */
        this.enableCB = new EventEmitter();
        /** Emitted when widgets are removed from the grid */
        this.removedCB = new EventEmitter();
        /** Emitted during widget resize operations */
        this.resizeCB = new EventEmitter();
        /** Emitted when widget resize starts */
        this.resizeStartCB = new EventEmitter();
        /** Emitted when widget resize stops */
        this.resizeStopCB = new EventEmitter();
        // set globally our method to create the right widget type
        if (!GridStack.addRemoveCB) {
            GridStack.addRemoveCB = gsCreateNgComponents;
        }
        if (!GridStack.saveCB) {
            GridStack.saveCB = gsSaveAdditionalNgInfo;
        }
        if (!GridStack.updateCB) {
            GridStack.updateCB = gsUpdateNgComponents;
        }
        this.el._gridComp = this;
    }
    /**
     * Grid configuration options.
     * Can be set before grid initialization or updated after grid is created.
     *
     * @example
     * ```typescript
     * gridOptions: GridStackOptions = {
     *   column: 12,
     *   cellHeight: 'auto',
     *   animate: true
     * };
     * ```
     */
    set options(o) {
        if (this._grid) {
            this._grid.updateOptions(o);
        }
        else {
            this._options = o;
        }
    }
    /** Get the current running grid options */
    get options() { return this._grid?.opts || this._options || {}; }
    /**
     * Get the native DOM element that contains grid-specific fields.
     * This element has GridStack properties attached to it.
     */
    get el() { return this.elementRef.nativeElement; }
    /**
     * Get the underlying GridStack instance.
     * Use this to access GridStack API methods directly.
     *
     * @example
     * ```typescript
     * this.gridComponent.grid.addWidget({x: 0, y: 0, w: 2, h: 1});
     * ```
     */
    get grid() { return this._grid; }
    /**
     * Register a list of Angular components for dynamic creation.
     *
     * @param typeList Array of component types to register
     *
     * @example
     * ```typescript
     * GridstackComponent.addComponentToSelectorType([
     *   MyWidgetComponent,
     *   AnotherWidgetComponent
     * ]);
     * ```
     */
    static addComponentToSelectorType(typeList) {
        typeList.forEach(type => GridstackComponent.selectorToType[GridstackComponent.getSelector(type)] = type);
    }
    /**
     * Extract the selector string from an Angular component type.
     *
     * @param type The component type to get selector from
     * @returns The component's selector string
     */
    static getSelector(type) {
        return reflectComponentType(type).selector;
    }
    ngOnInit() {
        // init ourself before any template children are created since we track them below anyway - no need to double create+update widgets
        this.loaded = !!this.options?.children?.length;
        this._grid = GridStack.init(this._options, this.el);
        delete this._options; // GS has it now
        this.checkEmpty();
    }
    /** wait until after all DOM is ready to init gridstack children (after angular ngFor and sub-components run first) */
    ngAfterContentInit() {
        // track whenever the children list changes and update the layout...
        this._sub = this.gridstackItems?.changes.subscribe(() => this.updateAll());
        // ...and do this once at least unless we loaded children already
        if (!this.loaded)
            this.updateAll();
        this.hookEvents(this.grid);
    }
    ngOnDestroy() {
        this.unhookEvents(this._grid);
        this._sub?.unsubscribe();
        this._grid?.destroy();
        delete this._grid;
        delete this.el._gridComp;
        delete this.container;
        delete this.ref;
    }
    /**
     * called when the TEMPLATE (not recommended) list of items changes - get a list of nodes and
     * update the layout accordingly (which will take care of adding/removing items changed by Angular)
     */
    updateAll() {
        if (!this.grid)
            return;
        const layout = [];
        this.gridstackItems?.forEach(item => {
            layout.push(item.options);
            item.clearOptions();
        });
        this.grid.load(layout); // efficient that does diffs only
    }
    /** check if the grid is empty, if so show alternative content */
    checkEmpty() {
        if (!this.grid)
            return;
        this.isEmpty = !this.grid.engine.nodes.length;
    }
    /** get all known events as easy to use Outputs for convenience */
    hookEvents(grid) {
        if (!grid)
            return;
        // nested grids don't have events in v12.1+ so skip
        if (grid.parentGridNode)
            return;
        grid
            .on('added', (event, nodes) => {
            const gridComp = nodes[0].grid?.el._gridComp || this;
            gridComp.checkEmpty();
            this.addedCB.emit({ event, nodes });
        })
            .on('change', (event, nodes) => this.changeCB.emit({ event, nodes }))
            .on('disable', (event) => this.disableCB.emit({ event }))
            .on('drag', (event, el) => this.dragCB.emit({ event, el }))
            .on('dragstart', (event, el) => this.dragStartCB.emit({ event, el }))
            .on('dragstop', (event, el) => this.dragStopCB.emit({ event, el }))
            .on('dropped', (event, previousNode, newNode) => this.droppedCB.emit({ event, previousNode, newNode }))
            .on('enable', (event) => this.enableCB.emit({ event }))
            .on('removed', (event, nodes) => {
            const gridComp = nodes[0].grid?.el._gridComp || this;
            gridComp.checkEmpty();
            this.removedCB.emit({ event, nodes });
        })
            .on('resize', (event, el) => this.resizeCB.emit({ event, el }))
            .on('resizestart', (event, el) => this.resizeStartCB.emit({ event, el }))
            .on('resizestop', (event, el) => this.resizeStopCB.emit({ event, el }));
    }
    unhookEvents(grid) {
        if (!grid)
            return;
        // nested grids don't have events in v12.1+ so skip
        if (grid.parentGridNode)
            return;
        grid.off('added change disable drag dragstart dragstop dropped enable removed resize resizestart resizestop');
    }
}
/**
 * Mapping of component selectors to their types for dynamic creation.
 *
 * This enables dynamic component instantiation from string selectors.
 * Angular doesn't provide public access to this mapping, so we maintain our own.
 *
 * @example
 * ```typescript
 * GridstackComponent.addComponentToSelectorType([MyWidgetComponent]);
 * ```
 */
GridstackComponent.selectorToType = {};
GridstackComponent.ɵfac = i0.ɵɵngDeclareFactory({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackComponent, deps: [{ token: i0.ElementRef }], target: i0.ɵɵFactoryTarget.Component });
GridstackComponent.ɵcmp = i0.ɵɵngDeclareComponent({ minVersion: "14.0.0", version: "14.3.0", type: GridstackComponent, isStandalone: true, selector: "gridstack", inputs: { options: "options", isEmpty: "isEmpty" }, outputs: { addedCB: "addedCB", changeCB: "changeCB", disableCB: "disableCB", dragCB: "dragCB", dragStartCB: "dragStartCB", dragStopCB: "dragStopCB", droppedCB: "droppedCB", enableCB: "enableCB", removedCB: "removedCB", resizeCB: "resizeCB", resizeStartCB: "resizeStartCB", resizeStopCB: "resizeStopCB" }, queries: [{ propertyName: "gridstackItems", predicate: GridstackItemComponent }], viewQueries: [{ propertyName: "container", first: true, predicate: ["container"], descendants: true, read: ViewContainerRef, static: true }], ngImport: i0, template: `
    <!-- content to show when when grid is empty, like instructions on how to add widgets -->
    <ng-content select="[empty-content]" *ngIf="isEmpty"></ng-content>
    <!-- where dynamic items go -->
    <ng-template #container></ng-template>
    <!-- where template items go -->
    <ng-content></ng-content>
  `, isInline: true, styles: [":host{display:block}\n"], dependencies: [{ kind: "directive", type: NgIf, selector: "[ngIf]", inputs: ["ngIf", "ngIfThen", "ngIfElse"] }] });
i0.ɵɵngDeclareClassMetadata({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackComponent, decorators: [{
            type: Component,
            args: [{ selector: 'gridstack', template: `
    <!-- content to show when when grid is empty, like instructions on how to add widgets -->
    <ng-content select="[empty-content]" *ngIf="isEmpty"></ng-content>
    <!-- where dynamic items go -->
    <ng-template #container></ng-template>
    <!-- where template items go -->
    <ng-content></ng-content>
  `, standalone: true, imports: [NgIf], styles: [":host{display:block}\n"] }]
        }], ctorParameters: function () { return [{ type: i0.ElementRef }]; }, propDecorators: { gridstackItems: [{
                type: ContentChildren,
                args: [GridstackItemComponent]
            }], container: [{
                type: ViewChild,
                args: ['container', { read: ViewContainerRef, static: true }]
            }], options: [{
                type: Input
            }], isEmpty: [{
                type: Input
            }], addedCB: [{
                type: Output
            }], changeCB: [{
                type: Output
            }], disableCB: [{
                type: Output
            }], dragCB: [{
                type: Output
            }], dragStartCB: [{
                type: Output
            }], dragStopCB: [{
                type: Output
            }], droppedCB: [{
                type: Output
            }], enableCB: [{
                type: Output
            }], removedCB: [{
                type: Output
            }], resizeCB: [{
                type: Output
            }], resizeStartCB: [{
                type: Output
            }], resizeStopCB: [{
                type: Output
            }] } });
/**
 * can be used when a new item needs to be created, which we do as a Angular component, or deleted (skip)
 **/
function gsCreateNgComponents(host, n, add, isGrid) {
    if (add) {
        //
        // create the component dynamically - see https://angular.io/docs/ts/latest/cookbook/dynamic-component-loader.html
        //
        if (!host)
            return;
        if (isGrid) {
            // TODO: figure out how to create ng component inside regular Div. need to access app injectors...
            // if (!container) {
            //   const hostElement: Element = host;
            //   const environmentInjector: EnvironmentInjector;
            //   grid = createComponent(GridstackComponent, {environmentInjector, hostElement})?.instance;
            // }
            const gridItemComp = host.parentElement?._gridItemComp;
            if (!gridItemComp)
                return;
            // check if gridItem has a child component with 'container' exposed to create under..
            const container = gridItemComp.childWidget?.container || gridItemComp.container;
            const gridRef = container?.createComponent(GridstackComponent);
            const grid = gridRef?.instance;
            if (!grid)
                return;
            grid.ref = gridRef;
            grid.options = n;
            return grid.el;
        }
        else {
            const gridComp = host._gridComp;
            const gridItemRef = gridComp?.container?.createComponent(GridstackItemComponent);
            const gridItem = gridItemRef?.instance;
            if (!gridItem)
                return;
            gridItem.ref = gridItemRef;
            // define what type of component to create as child, OR you can do it GridstackItemComponent template, but this is more generic
            const selector = n.selector;
            const type = selector ? GridstackComponent.selectorToType[selector] : undefined;
            if (type) {
                // shared code to create our selector component
                const createComp = () => {
                    const childWidget = gridItem.container?.createComponent(type)?.instance;
                    // if proper BaseWidget subclass, save it and load additional data
                    if (childWidget && typeof childWidget.serialize === 'function' && typeof childWidget.deserialize === 'function') {
                        gridItem.childWidget = childWidget;
                        childWidget.deserialize(n);
                    }
                };
                const lazyLoad = n.lazyLoad || n.grid?.opts?.lazyLoad && n.lazyLoad !== false;
                if (lazyLoad) {
                    if (!n.visibleObservable) {
                        n.visibleObservable = new IntersectionObserver(([entry]) => {
                            if (entry.isIntersecting) {
                                n.visibleObservable?.disconnect();
                                delete n.visibleObservable;
                                createComp();
                            }
                        });
                        window.setTimeout(() => n.visibleObservable?.observe(gridItem.el)); // wait until callee sets position attributes
                    }
                }
                else
                    createComp();
            }
            return gridItem.el;
        }
    }
    else {
        //
        // REMOVE - have to call ComponentRef:destroy() for dynamic objects to correctly remove themselves
        // Note: this will destroy all children dynamic components as well: gridItem -> childWidget
        //
        if (isGrid) {
            const grid = n.el?._gridComp;
            if (grid?.ref)
                grid.ref.destroy();
            else
                grid?.ngOnDestroy();
        }
        else {
            const gridItem = n.el?._gridItemComp;
            if (gridItem?.ref)
                gridItem.ref.destroy();
            else
                gridItem?.ngOnDestroy();
        }
    }
    return;
}
/**
 * called for each item in the grid - check if additional information needs to be saved.
 * Note: since this is options minus gridstack protected members using Utils.removeInternalForSave(),
 * this typically doesn't need to do anything. However your custom Component @Input() are now supported
 * using BaseWidget.serialize()
 */
function gsSaveAdditionalNgInfo(n, w) {
    const gridItem = n.el?._gridItemComp;
    if (gridItem) {
        const input = gridItem.childWidget?.serialize();
        if (input) {
            w.input = input;
        }
        return;
    }
    // else check if Grid
    const grid = n.el?._gridComp;
    if (grid) {
        //.... save any custom data
    }
}
/**
 * track when widgeta re updated (rather than created) to make sure we de-serialize them as well
 */
function gsUpdateNgComponents(n) {
    const w = n;
    const gridItem = n.el?._gridItemComp;
    if (gridItem?.childWidget && w.input)
        gridItem.childWidget.deserialize(w);
}

/**
 * gridstack.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
/**
 * @deprecated Use GridstackComponent and GridstackItemComponent as standalone components instead.
 *
 * This NgModule is provided for backward compatibility but is no longer the recommended approach.
 * Import components directly in your standalone components or use the new Angular module structure.
 *
 * @example
 * ```typescript
 * // Preferred approach - standalone components
 * @Component({
 *   selector: 'my-app',
 *   imports: [GridstackComponent, GridstackItemComponent],
 *   template: '<gridstack></gridstack>'
 * })
 * export class AppComponent {}
 *
 * // Legacy approach (deprecated)
 * @NgModule({
 *   imports: [GridstackModule]
 * })
 * export class AppModule {}
 * ```
 */
class GridstackModule {
}
GridstackModule.ɵfac = i0.ɵɵngDeclareFactory({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackModule, deps: [], target: i0.ɵɵFactoryTarget.NgModule });
GridstackModule.ɵmod = i0.ɵɵngDeclareNgModule({ minVersion: "14.0.0", version: "14.3.0", ngImport: i0, type: GridstackModule, imports: [GridstackItemComponent,
        GridstackComponent], exports: [GridstackItemComponent,
        GridstackComponent] });
GridstackModule.ɵinj = i0.ɵɵngDeclareInjector({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackModule, imports: [GridstackItemComponent,
        GridstackComponent] });
i0.ɵɵngDeclareClassMetadata({ minVersion: "12.0.0", version: "14.3.0", ngImport: i0, type: GridstackModule, decorators: [{
            type: NgModule,
            args: [{
                    imports: [
                        GridstackItemComponent,
                        GridstackComponent,
                    ],
                    exports: [
                        GridstackItemComponent,
                        GridstackComponent,
                    ],
                }]
        }] });

/*
 * Public API Surface of gridstack-angular
 */

/**
 * Generated bundle index. Do not edit.
 */

export { BaseWidget, GridstackComponent, GridstackItemComponent, GridstackModule, gsCreateNgComponents, gsSaveAdditionalNgInfo, gsUpdateNgComponents };
//# sourceMappingURL=gridstack-angular.mjs.map
