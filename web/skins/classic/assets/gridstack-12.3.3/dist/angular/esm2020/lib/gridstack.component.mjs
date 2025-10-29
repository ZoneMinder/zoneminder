/**
 * gridstack.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
import { Component, ContentChildren, EventEmitter, Input, Output, ViewChild, ViewContainerRef, reflectComponentType } from '@angular/core';
import { NgIf } from '@angular/common';
import { GridStack } from 'gridstack';
import { GridstackItemComponent } from './gridstack-item.component';
import * as i0 from "@angular/core";
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
export class GridstackComponent {
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
export function gsCreateNgComponents(host, n, add, isGrid) {
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
export function gsSaveAdditionalNgInfo(n, w) {
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
export function gsUpdateNgComponents(n) {
    const w = n;
    const gridItem = n.el?._gridItemComp;
    if (gridItem?.childWidget && w.input)
        gridItem.childWidget.deserialize(w);
}
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZ3JpZHN0YWNrLmNvbXBvbmVudC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIi4uLy4uLy4uLy4uL2FuZ3VsYXIvcHJvamVjdHMvbGliL3NyYy9saWIvZ3JpZHN0YWNrLmNvbXBvbmVudC50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7O0dBR0c7QUFFSCxPQUFPLEVBQ2UsU0FBUyxFQUFFLGVBQWUsRUFBYyxZQUFZLEVBQUUsS0FBSyxFQUMxRCxNQUFNLEVBQW1CLFNBQVMsRUFBRSxnQkFBZ0IsRUFBRSxvQkFBb0IsRUFDaEcsTUFBTSxlQUFlLENBQUM7QUFDdkIsT0FBTyxFQUFFLElBQUksRUFBRSxNQUFNLGlCQUFpQixDQUFDO0FBRXZDLE9BQU8sRUFBd0MsU0FBUyxFQUFvRCxNQUFNLFdBQVcsQ0FBQztBQUk5SCxPQUFPLEVBQTJCLHNCQUFzQixFQUFFLE1BQU0sNEJBQTRCLENBQUM7O0FBa0M3Rjs7Ozs7Ozs7Ozs7Ozs7Ozs7R0FpQkc7QUFrQkgsTUFBTSxPQUFPLGtCQUFrQjtJQXVLN0IsWUFBK0IsVUFBMkM7UUFBM0MsZUFBVSxHQUFWLFVBQVUsQ0FBaUM7UUFySDFFOzs7Ozs7Ozs7Ozs7O1dBYUc7UUFFSCxpREFBaUQ7UUFDaEMsWUFBTyxHQUFHLElBQUksWUFBWSxFQUFXLENBQUM7UUFFdkQsdUNBQXVDO1FBQ3RCLGFBQVEsR0FBRyxJQUFJLFlBQVksRUFBVyxDQUFDO1FBRXhELG9DQUFvQztRQUNuQixjQUFTLEdBQUcsSUFBSSxZQUFZLEVBQVcsQ0FBQztRQUV6RCw0Q0FBNEM7UUFDM0IsV0FBTSxHQUFHLElBQUksWUFBWSxFQUFhLENBQUM7UUFFeEQsc0NBQXNDO1FBQ3JCLGdCQUFXLEdBQUcsSUFBSSxZQUFZLEVBQWEsQ0FBQztRQUU3RCxxQ0FBcUM7UUFDcEIsZUFBVSxHQUFHLElBQUksWUFBWSxFQUFhLENBQUM7UUFFNUQscUNBQXFDO1FBQ3BCLGNBQVMsR0FBRyxJQUFJLFlBQVksRUFBYSxDQUFDO1FBRTNELG1DQUFt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SSxFQUFFO1FBQ1IsMkJBQTJCO0tBQzVCO0FBQ0gsQ0FBQztBQUVEOztHQUVHO0FBQ0gsTUFBTSxVQUFVLG9CQUFvQixDQUFDLENBQWtCO0lBQ3JELE1BQU0sQ0FBQyxHQUFzQixDQUFDLENBQUM7SUFDL0IsTUFBTSxRQUFRLEdBQUksQ0FBQyxDQUFDLEVBQThCLEVBQUUsYUFBYSxDQUFDO0lBQ2xFLElBQUksUUFBUSxFQUFFLFdBQVcsSUFBSSxDQUFDLENBQUMsS0FBSztRQUFFLFFBQVEsQ0FBQyxXQUFXLENBQUMsV0FBVyxDQUFDLENBQUMsQ0FBQyxDQUFDO0FBQzVFLENBQUMiLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIGdyaWRzdGFjay5jb21wb25lbnQudHMgMTIuMy4zXG4gKiBDb3B5cmlnaHQgKGMpIDIwMjItMjAyNCBBbGFpbiBEdW1lc255IC0gc2VlIEdyaWRTdGFjayByb290IGxpY2Vuc2VcbiAqL1xuXG5pbXBvcnQge1xuICAgIEFmdGVyQ29udGVudEluaXQsIENvbXBvbmVudCwgQ29udGVudENoaWxkcmVuLCBFbGVtZW50UmVmLCBFdmVudEVtaXR0ZXIsIElucHV0LFxuICAgIE9uRGVzdHJveSwgT25Jbml0LCBPdXRwdXQsIFF1ZXJ5TGlzdCwgVHlwZSwgVmlld0NoaWxkLCBWaWV3Q29udGFpbmVyUmVmLCByZWZsZWN0Q29tcG9uZW50VHlwZSwgQ29tcG9uZW50UmVmXG59IGZyb20gJ0Bhbmd1bGFyL2NvcmUnO1xuaW1wb3J0IHsgTmdJZiB9IGZyb20gJ0Bhbmd1bGFyL2NvbW1vbic7XG5pbXBvcnQgeyBTdWJzY3JpcHRpb24gfSBmcm9tICdyeGpzJztcbmltcG9ydCB7IEdyaWRIVE1MRWxlbWVudCwgR3JpZEl0ZW1IVE1MRWxlbWVudCwgR3JpZFN0YWNrLCBHcmlkU3RhY2tOb2RlLCBHcmlkU3RhY2tPcHRpb25zLCBHcmlkU3RhY2tXaWRnZXQgfSBmcm9tICdncmlkc3RhY2snO1xuXG5pbXBvcnQgeyBOZ0dyaWRTdGFja05vZGUsIE5nR3JpZFN0YWNrV2lkZ2V0IH0gZnJvbSAnLi90eXBlcyc7XG5pbXBvcnQgeyBCYXNlV2lkZ2V0IH0gZnJvbSAnLi9iYXNlLXdpZGdldCc7XG5pbXBvcnQgeyBHcmlkSXRlbUNvbXBIVE1MRWxlbWVudCwgR3JpZHN0YWNrSXRlbUNvbXBvbmVudCB9IGZyb20gJy4vZ3JpZHN0YWNrLWl0ZW0uY29tcG9uZW50JztcblxuLyoqXG4gKiBFdmVudCBoYW5kbGVyIGNhbGxiYWNrIHNpZ25hdHVyZXMgZm9yIGRpZmZlcmVudCBHcmlkU3RhY2sgZXZlbnRzLlxuICogVGhlc2UgdHlwZXMgZGVmaW5lIHRoZSBzdHJ1Y3R1cmUgb2YgZGF0YSBwYXNzZWQgdG8gQW5ndWxhciBldmVudCBlbWl0dGVycy5cbiAqL1xuXG4vKiogQ2FsbGJhY2sgZm9yIGdlbmVyYWwgZXZlbnRzIChlbmFibGUsIGRpc2FibGUsIGV0Yy4pICovXG5leHBvcnQgdHlwZSBldmVudENCID0ge2V2ZW50OiBFdmVudH07XG5cbi8qKiBDYWxsYmFjayBmb3IgZWxlbWVudC1zcGVjaWZpYyBldmVudHMgKHJlc2l6ZSwgZHJhZywgZXRjLikgKi9cbmV4cG9ydCB0eXBlIGVsZW1lbnRDQiA9IHtldmVudDogRXZlbnQsIGVsOiBHcmlkSXRlbUhUTUxFbGVtZW50fTtcblxuLyoqIENhbGxiYWNrIGZvciBldmVudHMgYWZmZWN0aW5nIG11bHRpcGxlIG5vZGVzIChjaGFuZ2UsIGV0Yy4pICovXG5leHBvcnQgdHlwZSBub2Rlc0NCID0ge2V2ZW50OiBFdmVudCwgbm9kZXM6IEdyaWRTdGFja05vZGVbXX07XG5cbi8qKiBDYWxsYmFjayBmb3IgZHJvcCBldmVudHMgd2l0aCBiZWZvcmUvYWZ0ZXIgbm9kZSBzdGF0ZSAqL1xuZXhwb3J0IHR5cGUgZHJvcHBlZENCID0ge2V2ZW50OiBFdmVudCwgcHJldmlvdXNOb2RlOiBHcmlkU3RhY2tOb2RlLCBuZXdOb2RlOiBHcmlkU3RhY2tOb2RlfTtcblxuLyoqXG4gKiBFeHRlbmRlZCBIVE1MRWxlbWVudCBpbnRlcmZhY2UgZm9yIHRoZSBncmlkIGNvbnRhaW5lci5cbiAqIFN0b3JlcyBhIGJhY2stcmVmZXJlbmNlIHRvIHRoZSBBbmd1bGFyIGNvbXBvbmVudCBmb3IgaW50ZWdyYXRpb24gcHVycG9zZXMuXG4gKi9cbmV4cG9ydCBpbnRlcmZhY2UgR3JpZENvbXBIVE1MRWxlbWVudCBleHRlbmRzIEdyaWRIVE1MRWxlbWVudCB7XG4gIC8qKiBCYWNrLXJlZmVyZW5jZSB0byB0aGUgQW5ndWxhciBHcmlkU3RhY2sgY29tcG9uZW50ICovXG4gIF9ncmlkQ29tcD86IEdyaWRzdGFja0NvbXBvbmVudDtcbn1cblxuLyoqXG4gKiBNYXBwaW5nIG9mIHNlbGVjdG9yIHN0cmluZ3MgdG8gQW5ndWxhciBjb21wb25lbnQgdHlwZXMuXG4gKiBVc2VkIGZvciBkeW5hbWljIGNvbXBvbmVudCBjcmVhdGlvbiBiYXNlZCBvbiB3aWRnZXQgc2VsZWN0b3JzLlxuICovXG5leHBvcnQgdHlwZSBTZWxlY3RvclRvVHlwZSA9IHtba2V5OiBzdHJpbmddOiBUeXBlPE9iamVjdD59O1xuXG4vKipcbiAqIEFuZ3VsYXIgY29tcG9uZW50IHdyYXBwZXIgZm9yIEdyaWRTdGFjay5cbiAqIFxuICogVGhpcyBjb21wb25lbnQgcHJvdmlkZXMgQW5ndWxhciBpbnRlZ3JhdGlvbiBmb3IgR3JpZFN0YWNrIGdyaWRzLCBoYW5kbGluZzpcbiAqIC0gR3JpZCBpbml0aWFsaXphdGlvbiBhbmQgbGlmZWN5Y2xlXG4gKiAtIER5bmFtaWMgY29tcG9uZW50IGNyZWF0aW9uIGFuZCBtYW5hZ2VtZW50XG4gKiAtIEV2ZW50IGJpbmRpbmcgYW5kIGVtaXNzaW9uXG4gKiAtIEludGVncmF0aW9uIHdpdGggQW5ndWxhciBjaGFuZ2UgZGV0ZWN0aW9uXG4gKiBcbiAqIFVzZSBpbiBjb21iaW5hdGlvbiB3aXRoIEdyaWRzdGFja0l0ZW1Db21wb25lbnQgZm9yIGluZGl2aWR1YWwgZ3JpZCBpdGVtcy5cbiAqIFxuICogQGV4YW1wbGVcbiAqIGBgYGh0bWxcbiAqIDxncmlkc3RhY2sgW29wdGlvbnNdPVwiZ3JpZE9wdGlvbnNcIiAoY2hhbmdlKT1cIm9uR3JpZENoYW5nZSgkZXZlbnQpXCI+XG4gKiAgIDxkaXYgZW1wdHktY29udGVudD5EcmFnIHdpZGdldHMgaGVyZTwvZGl2PlxuICogPC9ncmlkc3RhY2s+XG4gKiBgYGBcbiAqL1xuQENvbXBvbmVudCh7XG4gIHNlbGVjdG9yOiAnZ3JpZHN0YWNrJyxcbiAgdGVtcGxhdGU6IGBcbiAgICA8IS0tIGNvbnRlbnQgdG8gc2hvdyB3aGVuIHdoZW4gZ3JpZCBpcyBlbXB0eSwgbGlrZSBpbnN0cnVjdGlvbnMgb24gaG93IHRvIGFkZCB3aWRnZXRzIC0tPlxuICAgIDxuZy1jb250ZW50IHNlbGVjdD1cIltlbXB0eS1jb250ZW50XVwiICpuZ0lmPVwiaXNFbXB0eVwiPjwvbmctY29udGVudD5cbiAgICA8IS0tIHdoZXJlIGR5bmFtaWMgaXRlbXMgZ28gLS0+XG4gICAgPG5nLXRlbXBsYXRlICNjb250YWluZXI+PC9uZy10ZW1wbGF0ZT5cbiAgICA8IS0tIHdoZXJlIHRlbXBsYXRlIGl0ZW1zIGdvIC0tPlxuICAgIDxuZy1jb250ZW50PjwvbmctY29udGVudD5cbiAgYCxcbiAgc3R5bGVzOiBbYFxuICAgIDpob3N0IHsgZGlzcGxheTogYmxvY2s7IH1cbiAgYF0sXG4gIHN0YW5kYWxvbmU6IHRydWUsXG4gIGltcG9ydHM6IFtOZ0lmXVxuICAvLyBjaGFuZ2VEZXRlY3Rpb246IENoYW5nZURldGVjdGlvblN0cmF0ZWd5Lk9uUHVzaCwgLy8gSUZGIHlvdSB3YW50IHRvIG9wdGltaXplIGFuZCBjb250cm9sIHdoZW4gQ2hhbmdlRGV0ZWN0aW9uIG5lZWRzIHRvIGhhcHBlbi4uLlxufSlcbmV4cG9ydCBjbGFzcyBHcmlkc3RhY2tDb21wb25lbnQgaW1wbGVtZW50cyBPbkluaXQsIEFmdGVyQ29udGVudEluaXQsIE9uRGVzdHJveSB7XG5cbiAgLyoqXG4gICAqIExpc3Qgb2YgdGVtcGxhdGUtYmFzZWQgZ3JpZCBpdGVtcyAobm90IHJlY29tbWVuZGVkIGFwcHJvYWNoKS5cbiAgICogVXNlZCB0byBzeW5jIGJldHdlZW4gRE9NIGFuZCBHcmlkU3RhY2sgaW50ZXJuYWxzIHdoZW4gaXRlbXMgYXJlIGRlZmluZWQgaW4gdGVtcGxhdGVzLlxuICAgKiBQcmVmZXIgZHluYW1pYyBjb21wb25lbnQgY3JlYXRpb24gaW5zdGVhZC5cbiAgICovXG4gIEBDb250ZW50Q2hpbGRyZW4oR3JpZHN0YWNrSXRlbUNvbXBvbmVudCkgcHVibGljIGdyaWRzdGFja0l0ZW1zPzogUXVlcnlMaXN0PEdyaWRzdGFja0l0ZW1Db21wb25lbnQ+O1xuICAvKipcbiAgICogQ29udGFpbmVyIGZvciBkeW5hbWljIGNvbXBvbmVudCBjcmVhdGlvbiAocmVjb21tZW5kZWQgYXBwcm9hY2gpLlxuICAgKiBVc2VkIHRvIGFwcGVuZCBncmlkIGl0ZW1zIHByb2dyYW1tYXRpY2FsbHkgYXQgcnVudGltZS5cbiAgICovXG4gIEBWaWV3Q2hpbGQoJ2NvbnRhaW5lcicsIHsgcmVhZDogVmlld0NvbnRhaW5lclJlZiwgc3RhdGljOiB0cnVlfSkgcHVibGljIGNvbnRhaW5lcj86IFZpZXdDb250YWluZXJSZWY7XG5cbiAgLyoqXG4gICAqIEdyaWQgY29uZmlndXJhdGlvbiBvcHRpb25zLlxuICAgKiBDYW4gYmUgc2V0IGJlZm9yZSBncmlkIGluaXRpYWxpemF0aW9uIG9yIHVwZGF0ZWQgYWZ0ZXIgZ3JpZCBpcyBjcmVhdGVkLlxuICAgKiBcbiAgICogQGV4YW1wbGVcbiAgICogYGBgdHlwZXNjcmlwdFxuICAgKiBncmlkT3B0aW9uczogR3JpZFN0YWNrT3B0aW9ucyA9IHtcbiAgICogICBjb2x1bW46IDEyLFxuICAgKiAgIGNlbGxIZWlnaHQ6ICdhdXRvJyxcbiAgICogICBhbmltYXRlOiB0cnVlXG4gICAqIH07XG4gICAqIGBgYFxuICAgKi9cbiAgQElucHV0KCkgcHVibGljIHNldCBvcHRpb25zKG86IEdyaWRTdGFja09wdGlvbnMpIHtcbiAgICBpZiAodGhpcy5fZ3JpZCkge1xuICAgICAgdGhpcy5fZ3JpZC51cGRhdGVPcHRpb25zKG8pO1xuICAgIH0gZWxzZSB7XG4gICAgICB0aGlzLl9vcHRpb25zID0gbztcbiAgICB9XG4gIH1cbiAgLyoqIEdldCB0aGUgY3VycmVudCBydW5uaW5nIGdyaWQgb3B0aW9ucyAqL1xuICBwdWJsaWMgZ2V0IG9wdGlvbnMoKTogR3JpZFN0YWNrT3B0aW9ucyB7IHJldHVybiB0aGlzLl9ncmlkPy5vcHRzIHx8IHRoaXMuX29wdGlvbnMgfHwge307IH1cblxuICAvKipcbiAgICogQ29udHJvbHMgd2hldGhlciBlbXB0eSBjb250ZW50IHNob3VsZCBiZSBkaXNwbGF5ZWQuXG4gICAqIFNldCB0byB0cnVlIHRvIHNob3cgbmctY29udGVudCB3aXRoICdlbXB0eS1jb250ZW50JyBzZWxlY3RvciB3aGVuIGdyaWQgaGFzIG5vIGl0ZW1zLlxuICAgKiBcbiAgICogQGV4YW1wbGVcbiAgICogYGBgaHRtbFxuICAgKiA8Z3JpZHN0YWNrIFtpc0VtcHR5XT1cImdyaWRJdGVtcy5sZW5ndGggPT09IDBcIj5cbiAgICogICA8ZGl2IGVtcHR5LWNvbnRlbnQ+RHJhZyB3aWRnZXRzIGhlcmUgdG8gZ2V0IHN0YXJ0ZWQ8L2Rpdj5cbiAgICogPC9ncmlkc3RhY2s+XG4gICAqIGBgYFxuICAgKi9cbiAgQElucHV0KCkgcHVibGljIGlzRW1wdHk/OiBib29sZWFuO1xuXG4gIC8qKlxuICAgKiBHcmlkU3RhY2sgZXZlbnQgZW1pdHRlcnMgZm9yIEFuZ3VsYXIgaW50ZWdyYXRpb24uXG4gICAqIFxuICAgKiBUaGVzZSBwcm92aWRlIEFuZ3VsYXItc3R5bGUgZXZlbnQgaGFuZGxpbmcgZm9yIEdyaWRTdGFjayBldmVudHMuXG4gICAqIEFsdGVybmF0aXZlbHksIHVzZSBgdGhpcy5ncmlkLm9uKCdldmVudDEgZXZlbnQyJywgY2FsbGJhY2spYCBmb3IgbXVsdGlwbGUgZXZlbnRzLlxuICAgKiBcbiAgICogTm90ZTogJ0NCJyBzdWZmaXggcHJldmVudHMgY29uZmxpY3RzIHdpdGggbmF0aXZlIERPTSBldmVudHMuXG4gICAqIFxuICAgKiBAZXhhbXBsZVxuICAgKiBgYGBodG1sXG4gICAqIDxncmlkc3RhY2sgKGNoYW5nZUNCKT1cIm9uR3JpZENoYW5nZSgkZXZlbnQpXCIgKGRyb3BwZWRDQik9XCJvbkl0ZW1Ecm9wcGVkKCRldmVudClcIj5cbiAgICogPC9ncmlkc3RhY2s+XG4gICAqIGBgYFxuICAgKi9cbiAgXG4gIC8qKiBFbWl0dGVkIHdoZW4gd2lkZ2V0cyBhcmUgYWRkZWQgdG8gdGhlIGdyaWQgKi9cbiAgQE91dHB1dCgpIHB1YmxpYyBhZGRlZENCID0gbmV3IEV2ZW50RW1pdHRlcjxub2Rlc0NCPigpO1xuICBcbiAgLyoqIEVtaXR0ZWQgd2hlbiBncmlkIGxheW91dCBjaGFuZ2VzICovXG4gIEBPdXRwdXQoKSBwdWJsaWMgY2hhbmdlQ0IgPSBuZXcgRXZlbnRFbWl0dGVyPG5vZGVzQ0I+KCk7XG4gIFxuICAvKiogRW1pdHRlZCB3aGVuIGdyaWQgaXMgZGlzYWJsZWQgKi9cbiAgQE91dHB1dCgpIHB1YmxpYyBkaXNhYmxlQ0IgPSBuZXcgRXZlbnRFbWl0dGVyPGV2ZW50Q0I+KCk7XG4gIFxuICAvKiogRW1pdHRlZCBkdXJpbmcgd2lkZ2V0IGRyYWcgb3BlcmF0aW9ucyAqL1xuICBAT3V0cHV0KCkgcHVibGljIGRyYWdDQiA9IG5ldyBFdmVudEVtaXR0ZXI8ZWxlbWVudENCPigpO1xuICBcbiAgLyoqIEVtaXR0ZWQgd2hlbiB3aWRnZXQgZHJhZyBzdGFydHMgKi9cbiAgQE91dHB1dCgpIHB1YmxpYyBkcmFnU3RhcnRDQiA9IG5ldyBFdmVudEVtaXR0ZXI8ZWxlbWVudENCPigpO1xuICBcbiAgLyoqIEVtaXR0ZWQgd2hlbiB3aWRnZXQgZHJhZyBzdG9wcyAqL1xuICBAT3V0cHV0KCkgcHVibGljIGRyYWdTdG9wQ0IgPSBuZXcgRXZlbnRFbWl0dGVyPGVsZW1lbnRDQj4oKTtcbiAgXG4gIC8qKiBFbWl0dGVkIHdoZW4gd2lkZ2V0IGlzIGRyb3BwZWQgKi9cbiAgQE91dHB1dCgpIHB1YmxpYyBkcm9wcGVkQ0IgPSBuZXcgRXZlbnRFbWl0dGVyPGRyb3BwZWRDQj4oKTtcbiAgXG4gIC8qKiBFbWl0dGVkIHdoZW4gZ3JpZCBpcyBlbmFibGVkICovXG4gIEBPdXRwdXQoKSBwdWJsaWMgZW5hYmxlQ0IgPSBuZXcgRXZlbnRFbWl0dGVyPGV2ZW50Q0I+KCk7XG4gIFxuICAvKiogRW1pdHRlZCB3aGVuIHdpZGdldHMgYXJlIHJlbW92ZWQgZnJvbSB0aGUgZ3JpZCAqL1xuICBAT3V0cHV0KCkgcHVibGljIHJlbW92ZWRDQiA9IG5ldyBFdmVudEVtaXR0ZXI8bm9kZXNDQj4oKTtcbiAgXG4gIC8qKiBFbWl0dGVkIGR1cmluZyB3aWRnZXQgcmVzaXplIG9wZXJhdGlvbnMgKi9cbiAgQE91dHB1dCgpIHB1YmxpYyByZXNpemVDQiA9IG5ldyBFdmVudEVtaXR0ZXI8ZWxlbWVudENCPigpO1xuICBcbiAgLyoqIEVtaXR0ZWQgd2hlbiB3aWRnZXQgcmVzaXplIHN0YXJ0cyAqL1xuICBAT3V0cHV0KCkgcHVibGljIHJlc2l6ZVN0YXJ0Q0IgPSBuZXcgRXZlbnRFbWl0dGVyPGVsZW1lbnRDQj4oKTtcbiAgXG4gIC8qKiBFbWl0dGVkIHdoZW4gd2lkZ2V0IHJlc2l6ZSBzdG9wcyAqL1xuICBAT3V0cHV0KCkgcHVibGljIHJlc2l6ZVN0b3BDQiA9IG5ldyBFdmVudEVtaXR0ZXI8ZWxlbWVudENCPigpO1xuXG4gIC8qKlxuICAgKiBHZXQgdGhlIG5hdGl2ZSBET00gZWxlbWVudCB0aGF0IGNvbnRhaW5zIGdyaWQtc3BlY2lmaWMgZmllbGRzLlxuICAgKiBUaGlzIGVsZW1lbnQgaGFzIEdyaWRTdGFjayBwcm9wZXJ0aWVzIGF0dGFjaGVkIHRvIGl0LlxuICAgKi9cbiAgcHVibGljIGdldCBlbCgpOiBHcmlkQ29tcEhUTUxFbGVtZW50IHsgcmV0dXJuIHRoaXMuZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50OyB9XG5cbiAgLyoqXG4gICAqIEdldCB0aGUgdW5kZXJseWluZyBHcmlkU3RhY2sgaW5zdGFuY2UuXG4gICAqIFVzZSB0aGlzIHRvIGFjY2VzcyBHcmlkU3RhY2sgQVBJIG1ldGhvZHMgZGlyZWN0bHkuXG4gICAqIFxuICAgKiBAZXhhbXBsZVxuICAgKiBgYGB0eXBlc2NyaXB0XG4gICAqIHRoaXMuZ3JpZENvbXBvbmVudC5ncmlkLmFkZFdpZGdldCh7eDogMCwgeTogMCwgdzogMiwgaDogMX0pO1xuICAgKiBgYGBcbiAgICovXG4gIHB1YmxpYyBnZXQgZ3JpZCgpOiBHcmlkU3RhY2sgfCB1bmRlZmluZWQgeyByZXR1cm4gdGhpcy5fZ3JpZDsgfVxuXG4gIC8qKlxuICAgKiBDb21wb25lbnQgcmVmZXJlbmNlIGZvciBkeW5hbWljIGNvbXBvbmVudCByZW1vdmFsLlxuICAgKiBVc2VkIGludGVybmFsbHkgd2hlbiB0aGlzIGNvbXBvbmVudCBpcyBjcmVhdGVkIGR5bmFtaWNhbGx5LlxuICAgKi9cbiAgcHVibGljIHJlZjogQ29tcG9uZW50UmVmPEdyaWRzdGFja0NvbXBvbmVudD4gfCB1bmRlZmluZWQ7XG5cbiAgLyoqXG4gICAqIE1hcHBpbmcgb2YgY29tcG9uZW50IHNlbGVjdG9ycyB0byB0aGVpciB0eXBlcyBmb3IgZHluYW1pYyBjcmVhdGlvbi5cbiAgICogXG4gICAqIFRoaXMgZW5hYmxlcyBkeW5hbWljIGNvbXBvbmVudCBpbnN0YW50aWF0aW9uIGZyb20gc3RyaW5nIHNlbGVjdG9ycy5cbiAgICogQW5ndWxhciBkb2Vzbid0IHByb3ZpZGUgcHVibGljIGFjY2VzcyB0byB0aGlzIG1hcHBpbmcsIHNvIHdlIG1haW50YWluIG91ciBvd24uXG4gICAqIFxuICAgKiBAZXhhbXBsZVxuICAgKiBgYGB0eXBlc2NyaXB0XG4gICAqIEdyaWRzdGFja0NvbXBvbmVudC5hZGRDb21wb25lbnRUb1NlbGVjdG9yVHlwZShbTXlXaWRnZXRDb21wb25lbnRdKTtcbiAgICogYGBgXG4gICAqL1xuICBwdWJsaWMgc3RhdGljIHNlbGVjdG9yVG9UeXBlOiBTZWxlY3RvclRvVHlwZSA9IHt9O1xuICAvKipcbiAgICogUmVnaXN0ZXIgYSBsaXN0IG9mIEFuZ3VsYXIgY29tcG9uZW50cyBmb3IgZHluYW1pYyBjcmVhdGlvbi5cbiAgICogXG4gICAqIEBwYXJhbSB0eXBlTGlzdCBBcnJheSBvZiBjb21wb25lbnQgdHlwZXMgdG8gcmVnaXN0ZXJcbiAgICogXG4gICAqIEBleGFtcGxlXG4gICAqIGBgYHR5cGVzY3JpcHRcbiAgICogR3JpZHN0YWNrQ29tcG9uZW50LmFkZENvbXBvbmVudFRvU2VsZWN0b3JUeXBlKFtcbiAgICogICBNeVdpZGdldENvbXBvbmVudCxcbiAgICogICBBbm90aGVyV2lkZ2V0Q29tcG9uZW50XG4gICAqIF0pO1xuICAgKiBgYGBcbiAgICovXG4gIHB1YmxpYyBzdGF0aWMgYWRkQ29tcG9uZW50VG9TZWxlY3RvclR5cGUodHlwZUxpc3Q6IEFycmF5PFR5cGU8T2JqZWN0Pj4pIHtcbiAgICB0eXBlTGlzdC5mb3JFYWNoKHR5cGUgPT4gR3JpZHN0YWNrQ29tcG9uZW50LnNlbGVjdG9yVG9UeXBlWyBHcmlkc3RhY2tDb21wb25lbnQuZ2V0U2VsZWN0b3IodHlwZSkgXSA9IHR5cGUpO1xuICB9XG4gIC8qKlxuICAgKiBFeHRyYWN0IHRoZSBzZWxlY3RvciBzdHJpbmcgZnJvbSBhbiBBbmd1bGFyIGNvbXBvbmVudCB0eXBlLlxuICAgKiBcbiAgICogQHBhcmFtIHR5cGUgVGhlIGNvbXBvbmVudCB0eXBlIHRvIGdldCBzZWxlY3RvciBmcm9tXG4gICAqIEByZXR1cm5zIFRoZSBjb21wb25lbnQncyBzZWxlY3RvciBzdHJpbmdcbiAgICovXG4gIHB1YmxpYyBzdGF0aWMgZ2V0U2VsZWN0b3IodHlwZTogVHlwZTxPYmplY3Q+KTogc3RyaW5nIHtcbiAgICByZXR1cm4gcmVmbGVjdENvbXBvbmVudFR5cGUodHlwZSkhLnNlbGVjdG9yO1xuICB9XG5cbiAgcHJvdGVjdGVkIF9vcHRpb25zPzogR3JpZFN0YWNrT3B0aW9ucztcbiAgcHJvdGVjdGVkIF9ncmlkPzogR3JpZFN0YWNrO1xuICBwcm90ZWN0ZWQgX3N1YjogU3Vic2NyaXB0aW9uIHwgdW5kZWZpbmVkO1xuICBwcm90ZWN0ZWQgbG9hZGVkPzogYm9vbGVhbjtcblxuICBjb25zdHJ1Y3Rvcihwcm90ZWN0ZWQgcmVhZG9ubHkgZWxlbWVudFJlZjogRWxlbWVudFJlZjxHcmlkQ29tcEhUTUxFbGVtZW50Pikge1xuICAgIC8vIHNldCBnbG9iYWxseSBvdXIgbWV0aG9kIHRvIGNyZWF0ZSB0aGUgcmlnaHQgd2lkZ2V0IHR5cGVcbiAgICBpZiAoIUdyaWRTdGFjay5hZGRSZW1vdmVDQikge1xuICAgICAgR3JpZFN0YWNrLmFkZFJlbW92ZUNCID0gZ3NDcmVhdGVOZ0NvbXBvbmVudHM7XG4gICAgfVxuICAgIGlmICghR3JpZFN0YWNrLnNhdmVDQikge1xuICAgICAgR3JpZFN0YWNrLnNhdmVDQiA9IGdzU2F2ZUFkZGl0aW9uYWxOZ0luZm87XG4gICAgfVxuICAgIGlmICghR3JpZFN0YWNrLnVwZGF0ZUNCKSB7XG4gICAgICBHcmlkU3RhY2sudXBkYXRlQ0IgPSBnc1VwZGF0ZU5nQ29tcG9uZW50cztcbiAgICB9XG4gICAgdGhpcy5lbC5fZ3JpZENvbXAgPSB0aGlzO1xuICB9XG5cbiAgcHVibGljIG5nT25Jbml0KCk6IHZvaWQge1xuICAgIC8vIGluaXQgb3Vyc2VsZiBiZWZvcmUgYW55IHRlbXBsYXRlIGNoaWxkcmVuIGFyZSBjcmVhdGVkIHNpbmNlIHdlIHRyYWNrIHRoZW0gYmVsb3cgYW55d2F5IC0gbm8gbmVlZCB0byBkb3VibGUgY3JlYXRlK3VwZGF0ZSB3aWRnZXRzXG4gICAgdGhpcy5sb2FkZWQgPSAhIXRoaXMub3B0aW9ucz8uY2hpbGRyZW4/Lmxlbmd0aDtcbiAgICB0aGlzLl9ncmlkID0gR3JpZFN0YWNrLmluaXQodGhpcy5fb3B0aW9ucywgdGhpcy5lbCk7XG4gICAgZGVsZXRlIHRoaXMuX29wdGlvbnM7IC8vIEdTIGhhcyBpdCBub3dcblxuICAgIHRoaXMuY2hlY2tFbXB0eSgpO1xuICB9XG5cbiAgLyoqIHdhaXQgdW50aWwgYWZ0ZXIgYWxsIERPTSBpcyByZWFkeSB0byBpbml0IGdyaWRzdGFjayBjaGlsZHJlbiAoYWZ0ZXIgYW5ndWxhciBuZ0ZvciBhbmQgc3ViLWNvbXBvbmVudHMgcnVuIGZpcnN0KSAqL1xuICBwdWJsaWMgbmdBZnRlckNvbnRlbnRJbml0KCk6IHZvaWQge1xuICAgIC8vIHRyYWNrIHdoZW5ldmVyIHRoZSBjaGlsZHJlbiBsaXN0IGNoYW5nZXMgYW5kIHVwZGF0ZSB0aGUgbGF5b3V0Li4uXG4gICAgdGhpcy5fc3ViID0gdGhpcy5ncmlkc3RhY2tJdGVtcz8uY2hhbmdlcy5zdWJzY3JpYmUoKCkgPT4gdGhpcy51cGRhdGVBbGwoKSk7XG4gICAgLy8gLi4uYW5kIGRvIHRoaXMgb25jZSBhdCBsZWFzdCB1bmxlc3Mgd2UgbG9hZGVkIGNoaWxkcmVuIGFscmVhZHlcbiAgICBpZiAoIXRoaXMubG9hZGVkKSB0aGlzLnVwZGF0ZUFsbCgpO1xuICAgIHRoaXMuaG9va0V2ZW50cyh0aGlzLmdyaWQpO1xuICB9XG5cbiAgcHVibGljIG5nT25EZXN0cm95KCk6IHZvaWQge1xuICAgIHRoaXMudW5ob29rRXZlbnRzKHRoaXMuX2dyaWQpO1xuICAgIHRoaXMuX3N1Yj8udW5zdWJzY3JpYmUoKTtcbiAgICB0aGlzLl9ncmlkPy5kZXN0cm95KCk7XG4gICAgZGVsZXRlIHRoaXMuX2dyaWQ7XG4gICAgZGVsZXRlIHRoaXMuZWwuX2dyaWRDb21wO1xuICAgIGRlbGV0ZSB0aGlzLmNvbnRhaW5lcjtcbiAgICBkZWxldGUgdGhpcy5yZWY7XG4gIH1cblxuICAvKipcbiAgICogY2FsbGVkIHdoZW4gdGhlIFRFTVBMQVRFIChub3QgcmVjb21tZW5kZWQpIGxpc3Qgb2YgaXRlbXMgY2hhbmdlcyAtIGdldCBhIGxpc3Qgb2Ygbm9kZXMgYW5kXG4gICAqIHVwZGF0ZSB0aGUgbGF5b3V0IGFjY29yZGluZ2x5ICh3aGljaCB3aWxsIHRha2UgY2FyZSBvZiBhZGRpbmcvcmVtb3ZpbmcgaXRlbXMgY2hhbmdlZCBieSBBbmd1bGFyKVxuICAgKi9cbiAgcHVibGljIHVwZGF0ZUFsbCgpIHtcbiAgICBpZiAoIXRoaXMuZ3JpZCkgcmV0dXJuO1xuICAgIGNvbnN0IGxheW91dDogR3JpZFN0YWNrV2lkZ2V0W10gPSBbXTtcbiAgICB0aGlzLmdyaWRzdGFja0l0ZW1zPy5mb3JFYWNoKGl0ZW0gPT4ge1xuICAgICAgbGF5b3V0LnB1c2goaXRlbS5vcHRpb25zKTtcbiAgICAgIGl0ZW0uY2xlYXJPcHRpb25zKCk7XG4gICAgfSk7XG4gICAgdGhpcy5ncmlkLmxvYWQobGF5b3V0KTsgLy8gZWZmaWNpZW50IHRoYXQgZG9lcyBkaWZmcyBvbmx5XG4gIH1cblxuICAvKiogY2hlY2sgaWYgdGhlIGdyaWQgaXMgZW1wdHksIGlmIHNvIHNob3cgYWx0ZXJuYXRpdmUgY29udGVudCAqL1xuICBwdWJsaWMgY2hlY2tFbXB0eSgpIHtcbiAgICBpZiAoIXRoaXMuZ3JpZCkgcmV0dXJuO1xuICAgIHRoaXMuaXNFbXB0eSA9ICF0aGlzLmdyaWQuZW5naW5lLm5vZGVzLmxlbmd0aDtcbiAgfVxuXG4gIC8qKiBnZXQgYWxsIGtub3duIGV2ZW50cyBhcyBlYXN5IHRvIHVzZSBPdXRwdXRzIGZvciBjb252ZW5pZW5jZSAqL1xuICBwcm90ZWN0ZWQgaG9va0V2ZW50cyhncmlkPzogR3JpZFN0YWNrKSB7XG4gICAgaWYgKCFncmlkKSByZXR1cm47XG4gICAgLy8gbmVzdGVkIGdyaWRzIGRvbid0IGhhdmUgZXZlbnRzIGluIHYxMi4xKyBzbyBza2lwXG4gICAgaWYgKGdyaWQucGFyZW50R3JpZE5vZGUpIHJldHVybjtcbiAgICBncmlkXG4gICAgICAub24oJ2FkZGVkJywgKGV2ZW50OiBFdmVudCwgbm9kZXM6IEdyaWRTdGFja05vZGVbXSkgPT4ge1xuICAgICAgICBjb25zdCBncmlkQ29tcCA9IChub2Rlc1swXS5ncmlkPy5lbCBhcyBHcmlkQ29tcEhUTUxFbGVtZW50KS5fZ3JpZENvbXAgfHwgdGhpcztcbiAgICAgICAgZ3JpZENvbXAuY2hlY2tFbXB0eSgpO1xuICAgICAgICB0aGlzLmFkZGVkQ0IuZW1pdCh7ZXZlbnQsIG5vZGVzfSk7XG4gICAgICB9KVxuICAgICAgLm9uKCdjaGFuZ2UnLCAoZXZlbnQ6IEV2ZW50LCBub2RlczogR3JpZFN0YWNrTm9kZVtdKSA9PiB0aGlzLmNoYW5nZUNCLmVtaXQoe2V2ZW50LCBub2Rlc30pKVxuICAgICAgLm9uKCdkaXNhYmxlJywgKGV2ZW50OiBFdmVudCkgPT4gdGhpcy5kaXNhYmxlQ0IuZW1pdCh7ZXZlbnR9KSlcbiAgICAgIC5vbignZHJhZycsIChldmVudDogRXZlbnQsIGVsOiBHcmlkSXRlbUhUTUxFbGVtZW50KSA9PiB0aGlzLmRyYWdDQi5lbWl0KHtldmVudCwgZWx9KSlcbiAgICAgIC5vbignZHJhZ3N0YXJ0JywgKGV2ZW50OiBFdmVudCwgZWw6IEdyaWRJdGVtSFRNTEVsZW1lbnQpID0+IHRoaXMuZHJhZ1N0YXJ0Q0IuZW1pdCh7ZXZlbnQsIGVsfSkpXG4gICAgICAub24oJ2RyYWdzdG9wJywgKGV2ZW50OiBFdmVudCwgZWw6IEdyaWRJdGVtSFRNTEVsZW1lbnQpID0+IHRoaXMuZHJhZ1N0b3BDQi5lbWl0KHtldmVudCwgZWx9KSlcbiAgICAgIC5vbignZHJvcHBlZCcsIChldmVudDogRXZlbnQsIHByZXZpb3VzTm9kZTogR3JpZFN0YWNrTm9kZSwgbmV3Tm9kZTogR3JpZFN0YWNrTm9kZSkgPT4gdGhpcy5kcm9wcGVkQ0IuZW1pdCh7ZXZlbnQsIHByZXZpb3VzTm9kZSwgbmV3Tm9kZX0pKVxuICAgICAgLm9uKCdlbmFibGUnLCAoZXZlbnQ6IEV2ZW50KSA9PiB0aGlzLmVuYWJsZUNCLmVtaXQoe2V2ZW50fSkpXG4gICAgICAub24oJ3JlbW92ZWQnLCAoZXZlbnQ6IEV2ZW50LCBub2RlczogR3JpZFN0YWNrTm9kZVtdKSA9PiB7XG4gICAgICAgIGNvbnN0IGdyaWRDb21wID0gKG5vZGVzWzBdLmdyaWQ/LmVsIGFzIEdyaWRDb21wSFRNTEVsZW1lbnQpLl9ncmlkQ29tcCB8fCB0aGlzO1xuICAgICAgICBncmlkQ29tcC5jaGVja0VtcHR5KCk7XG4gICAgICAgIHRoaXMucmVtb3ZlZENCLmVtaXQoe2V2ZW50LCBub2Rlc30pO1xuICAgICAgfSlcbiAgICAgIC5vbigncmVzaXplJywgKGV2ZW50OiBFdmVudCwgZWw6IEdyaWRJdGVtSFRNTEVsZW1lbnQpID0+IHRoaXMucmVzaXplQ0IuZW1pdCh7ZXZlbnQsIGVsfSkpXG4gICAgICAub24oJ3Jlc2l6ZXN0YXJ0JywgKGV2ZW50OiBFdmVudCwgZWw6IEdyaWRJdGVtSFRNTEVsZW1lbnQpID0+IHRoaXMucmVzaXplU3RhcnRDQi5lbWl0KHtldmVudCwgZWx9KSlcbiAgICAgIC5vbigncmVzaXplc3RvcCcsIChldmVudDogRXZlbnQsIGVsOiBHcmlkSXRlbUhUTUxFbGVtZW50KSA9PiB0aGlzLnJlc2l6ZVN0b3BDQi5lbWl0KHtldmVudCwgZWx9KSlcbiAgfVxuXG4gIHByb3RlY3RlZCB1bmhvb2tFdmVudHMoZ3JpZD86IEdyaWRTdGFjaykge1xuICAgIGlmICghZ3JpZCkgcmV0dXJuO1xuICAgIC8vIG5lc3RlZCBncmlkcyBkb24ndCBoYXZlIGV2ZW50cyBpbiB2MTIuMSsgc28gc2tpcFxuICAgIGlmIChncmlkLnBhcmVudEdyaWROb2RlKSByZXR1cm47XG4gICAgZ3JpZC5vZmYoJ2FkZGVkIGNoYW5nZSBkaXNhYmxlIGRyYWcgZHJhZ3N0YXJ0IGRyYWdzdG9wIGRyb3BwZWQgZW5hYmxlIHJlbW92ZWQgcmVzaXplIHJlc2l6ZXN0YXJ0IHJlc2l6ZXN0b3AnKTtcbiAgfVxufVxuXG4vKipcbiAqIGNhbiBiZSB1c2VkIHdoZW4gYSBuZXcgaXRlbSBuZWVkcyB0byBiZSBjcmVhdGVkLCB3aGljaCB3ZSBkbyBhcyBhIEFuZ3VsYXIgY29tcG9uZW50LCBvciBkZWxldGVkIChza2lwKVxuICoqL1xuZXhwb3J0IGZ1bmN0aW9uIGdzQ3JlYXRlTmdDb21wb25lbnRzKGhvc3Q6IEdyaWRDb21wSFRNTEVsZW1lbnQgfCBIVE1MRWxlbWVudCwgbjogTmdHcmlkU3RhY2tOb2RlLCBhZGQ6IGJvb2xlYW4sIGlzR3JpZDogYm9vbGVhbik6IEhUTUxFbGVtZW50IHwgdW5kZWZpbmVkIHtcbiAgaWYgKGFkZCkge1xuICAgIC8vXG4gICAgLy8gY3JlYXRlIHRoZSBjb21wb25lbnQgZHluYW1pY2FsbHkgLSBzZWUgaHR0cHM6Ly9hbmd1bGFyLmlvL2RvY3MvdHMvbGF0ZXN0L2Nvb2tib29rL2R5bmFtaWMtY29tcG9uZW50LWxvYWRlci5odG1sXG4gICAgLy9cbiAgICBpZiAoIWhvc3QpIHJldHVybjtcbiAgICBpZiAoaXNHcmlkKSB7XG4gICAgICAvLyBUT0RPOiBmaWd1cmUgb3V0IGhvdyB0byBjcmVhdGUgbmcgY29tcG9uZW50IGluc2lkZSByZWd1bGFyIERpdi4gbmVlZCB0byBhY2Nlc3MgYXBwIGluamVjdG9ycy4uLlxuICAgICAgLy8gaWYgKCFjb250YWluZXIpIHtcbiAgICAgIC8vICAgY29uc3QgaG9zdEVsZW1lbnQ6IEVsZW1lbnQgPSBob3N0O1xuICAgICAgLy8gICBjb25zdCBlbnZpcm9ubWVudEluamVjdG9yOiBFbnZpcm9ubWVudEluamVjdG9yO1xuICAgICAgLy8gICBncmlkID0gY3JlYXRlQ29tcG9uZW50KEdyaWRzdGFja0NvbXBvbmVudCwge2Vudmlyb25tZW50SW5qZWN0b3IsIGhvc3RFbGVtZW50fSk/Lmluc3RhbmNlO1xuICAgICAgLy8gfVxuXG4gICAgICBjb25zdCBncmlkSXRlbUNvbXAgPSAoaG9zdC5wYXJlbnRFbGVtZW50IGFzIEdyaWRJdGVtQ29tcEhUTUxFbGVtZW50KT8uX2dyaWRJdGVtQ29tcDtcbiAgICAgIGlmICghZ3JpZEl0ZW1Db21wKSByZXR1cm47XG4gICAgICAvLyBjaGVjayBpZiBncmlkSXRlbSBoYXMgYSBjaGlsZCBjb21wb25lbnQgd2l0aCAnY29udGFpbmVyJyBleHBvc2VkIHRvIGNyZWF0ZSB1bmRlci4uXG4gICAgICBjb25zdCBjb250YWluZXIgPSAoZ3JpZEl0ZW1Db21wLmNoaWxkV2lkZ2V0IGFzIGFueSk/LmNvbnRhaW5lciB8fCBncmlkSXRlbUNvbXAuY29udGFpbmVyO1xuICAgICAgY29uc3QgZ3JpZFJlZiA9IGNvbnRhaW5lcj8uY3JlYXRlQ29tcG9uZW50KEdyaWRzdGFja0NvbXBvbmVudCk7XG4gICAgICBjb25zdCBncmlkID0gZ3JpZFJlZj8uaW5zdGFuY2U7XG4gICAgICBpZiAoIWdyaWQpIHJldHVybjtcbiAgICAgIGdyaWQucmVmID0gZ3JpZFJlZjtcbiAgICAgIGdyaWQub3B0aW9ucyA9IG47XG4gICAgICByZXR1cm4gZ3JpZC5lbDtcbiAgICB9IGVsc2Uge1xuICAgICAgY29uc3QgZ3JpZENvbXAgPSAoaG9zdCBhcyBHcmlkQ29tcEhUTUxFbGVtZW50KS5fZ3JpZENvbXA7XG4gICAgICBjb25zdCBncmlkSXRlbVJlZiA9IGdyaWRDb21wPy5jb250YWluZXI/LmNyZWF0ZUNvbXBvbmVudChHcmlkc3RhY2tJdGVtQ29tcG9uZW50KTtcbiAgICAgIGNvbnN0IGdyaWRJdGVtID0gZ3JpZEl0ZW1SZWY/Lmluc3RhbmNlO1xuICAgICAgaWYgKCFncmlkSXRlbSkgcmV0dXJuO1xuICAgICAgZ3JpZEl0ZW0ucmVmID0gZ3JpZEl0ZW1SZWZcblxuICAgICAgLy8gZGVmaW5lIHdoYXQgdHlwZSBvZiBjb21wb25lbnQgdG8gY3JlYXRlIGFzIGNoaWxkLCBPUiB5b3UgY2FuIGRvIGl0IEdyaWRzdGFja0l0ZW1Db21wb25lbnQgdGVtcGxhdGUsIGJ1dCB0aGlzIGlzIG1vcmUgZ2VuZXJpY1xuICAgICAgY29uc3Qgc2VsZWN0b3IgPSBuLnNlbGVjdG9yO1xuICAgICAgY29uc3QgdHlwZSA9IHNlbGVjdG9yID8gR3JpZHN0YWNrQ29tcG9uZW50LnNlbGVjdG9yVG9UeXBlW3NlbGVjdG9yXSA6IHVuZGVmaW5lZDtcbiAgICAgIGlmICh0eXBlKSB7XG4gICAgICAgIC8vIHNoYXJlZCBjb2RlIHRvIGNyZWF0ZSBvdXIgc2VsZWN0b3IgY29tcG9uZW50XG4gICAgICAgIGNvbnN0IGNyZWF0ZUNvbXAgPSAoKSA9PiB7XG4gICAgICAgICAgY29uc3QgY2hpbGRXaWRnZXQgPSBncmlkSXRlbS5jb250YWluZXI/LmNyZWF0ZUNvbXBvbmVudCh0eXBlKT8uaW5zdGFuY2UgYXMgQmFzZVdpZGdldDtcbiAgICAgICAgICAvLyBpZiBwcm9wZXIgQmFzZVdpZGdldCBzdWJjbGFzcywgc2F2ZSBpdCBhbmQgbG9hZCBhZGRpdGlvbmFsIGRhdGFcbiAgICAgICAgICBpZiAoY2hpbGRXaWRnZXQgJiYgdHlwZW9mIGNoaWxkV2lkZ2V0LnNlcmlhbGl6ZSA9PT0gJ2Z1bmN0aW9uJyAmJiB0eXBlb2YgY2hpbGRXaWRnZXQuZGVzZXJpYWxpemUgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgIGdyaWRJdGVtLmNoaWxkV2lkZ2V0ID0gY2hpbGRXaWRnZXQ7XG4gICAgICAgICAgICBjaGlsZFdpZGdldC5kZXNlcmlhbGl6ZShuKTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCBsYXp5TG9hZCA9IG4ubGF6eUxvYWQgfHwgbi5ncmlkPy5vcHRzPy5sYXp5TG9hZCAmJiBuLmxhenlMb2FkICE9PSBmYWxzZTtcbiAgICAgICAgaWYgKGxhenlMb2FkKSB7XG4gICAgICAgICAgaWYgKCFuLnZpc2libGVPYnNlcnZhYmxlKSB7XG4gICAgICAgICAgICBuLnZpc2libGVPYnNlcnZhYmxlID0gbmV3IEludGVyc2VjdGlvbk9ic2VydmVyKChbZW50cnldKSA9PiB7IGlmIChlbnRyeS5pc0ludGVyc2VjdGluZykge1xuICAgICAgICAgICAgICBuLnZpc2libGVPYnNlcnZhYmxlPy5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICAgIGRlbGV0ZSBuLnZpc2libGVPYnNlcnZhYmxlO1xuICAgICAgICAgICAgICBjcmVhdGVDb21wKCk7XG4gICAgICAgICAgICB9fSk7XG4gICAgICAgICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiBuLnZpc2libGVPYnNlcnZhYmxlPy5vYnNlcnZlKGdyaWRJdGVtLmVsKSk7IC8vIHdhaXQgdW50aWwgY2FsbGVlIHNldHMgcG9zaXRpb24gYXR0cmlidXRlc1xuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIGNyZWF0ZUNvbXAoKTtcbiAgICAgIH1cblxuICAgICAgcmV0dXJuIGdyaWRJdGVtLmVsO1xuICAgIH1cbiAgfSBlbHNlIHtcbiAgICAvL1xuICAgIC8vIFJFTU9WRSAtIGhhdmUgdG8gY2FsbCBDb21wb25lbnRSZWY6ZGVzdHJveSgpIGZvciBkeW5hbWljIG9iamVjdHMgdG8gY29ycmVjdGx5IHJlbW92ZSB0aGVtc2VsdmVzXG4gICAgLy8gTm90ZTogdGhpcyB3aWxsIGRlc3Ryb3kgYWxsIGNoaWxkcmVuIGR5bmFtaWMgY29tcG9uZW50cyBhcyB3ZWxsOiBncmlkSXRlbSAtPiBjaGlsZFdpZGdldFxuICAgIC8vXG4gICAgaWYgKGlzR3JpZCkge1xuICAgICAgY29uc3QgZ3JpZCA9IChuLmVsIGFzIEdyaWRDb21wSFRNTEVsZW1lbnQpPy5fZ3JpZENvbXA7XG4gICAgICBpZiAoZ3JpZD8ucmVmKSBncmlkLnJlZi5kZXN0cm95KCk7XG4gICAgICBlbHNlIGdyaWQ/Lm5nT25EZXN0cm95KCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGNvbnN0IGdyaWRJdGVtID0gKG4uZWwgYXMgR3JpZEl0ZW1Db21wSFRNTEVsZW1lbnQpPy5fZ3JpZEl0ZW1Db21wO1xuICAgICAgaWYgKGdyaWRJdGVtPy5yZWYpIGdyaWRJdGVtLnJlZi5kZXN0cm95KCk7XG4gICAgICBlbHNlIGdyaWRJdGVtPy5uZ09uRGVzdHJveSgpO1xuICAgIH1cbiAgfVxuICByZXR1cm47XG59XG5cbi8qKlxuICogY2FsbGVkIGZvciBlYWNoIGl0ZW0gaW4gdGhlIGdyaWQgLSBjaGVjayBpZiBhZGRpdGlvbmFsIGluZm9ybWF0aW9uIG5lZWRzIHRvIGJlIHNhdmVkLlxuICogTm90ZTogc2luY2UgdGhpcyBpcyBvcHRpb25zIG1pbnVzIGdyaWRzdGFjayBwcm90ZWN0ZWQgbWVtYmVycyB1c2luZyBVdGlscy5yZW1vdmVJbnRlcm5hbEZvclNhdmUoKSxcbiAqIHRoaXMgdHlwaWNhbGx5IGRvZXNuJ3QgbmVlZCB0byBkbyBhbnl0aGluZy4gSG93ZXZlciB5b3VyIGN1c3RvbSBDb21wb25lbnQgQElucHV0KCkgYXJlIG5vdyBzdXBwb3J0ZWRcbiAqIHVzaW5nIEJhc2VXaWRnZXQuc2VyaWFsaXplKClcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGdzU2F2ZUFkZGl0aW9uYWxOZ0luZm8objogTmdHcmlkU3RhY2tOb2RlLCB3OiBOZ0dyaWRTdGFja1dpZGdldCkge1xuICBjb25zdCBncmlkSXRlbSA9IChuLmVsIGFzIEdyaWRJdGVtQ29tcEhUTUxFbGVtZW50KT8uX2dyaWRJdGVtQ29tcDtcbiAgaWYgKGdyaWRJdGVtKSB7XG4gICAgY29uc3QgaW5wdXQgPSBncmlkSXRlbS5jaGlsZFdpZGdldD8uc2VyaWFsaXplKCk7XG4gICAgaWYgKGlucHV0KSB7XG4gICAgICB3LmlucHV0ID0gaW5wdXQ7XG4gICAgfVxuICAgIHJldHVybjtcbiAgfVxuICAvLyBlbHNlIGNoZWNrIGlmIEdyaWRcbiAgY29uc3QgZ3JpZCA9IChuLmVsIGFzIEdyaWRDb21wSFRNTEVsZW1lbnQpPy5fZ3JpZENvbXA7XG4gIGlmIChncmlkKSB7XG4gICAgLy8uLi4uIHNhdmUgYW55IGN1c3RvbSBkYXRhXG4gIH1cbn1cblxuLyoqXG4gKiB0cmFjayB3aGVuIHdpZGdldGEgcmUgdXBkYXRlZCAocmF0aGVyIHRoYW4gY3JlYXRlZCkgdG8gbWFrZSBzdXJlIHdlIGRlLXNlcmlhbGl6ZSB0aGVtIGFzIHdlbGxcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGdzVXBkYXRlTmdDb21wb25lbnRzKG46IE5nR3JpZFN0YWNrTm9kZSkge1xuICBjb25zdCB3OiBOZ0dyaWRTdGFja1dpZGdldCA9IG47XG4gIGNvbnN0IGdyaWRJdGVtID0gKG4uZWwgYXMgR3JpZEl0ZW1Db21wSFRNTEVsZW1lbnQpPy5fZ3JpZEl0ZW1Db21wO1xuICBpZiAoZ3JpZEl0ZW0/LmNoaWxkV2lkZ2V0ICYmIHcuaW5wdXQpIGdyaWRJdGVtLmNoaWxkV2lkZ2V0LmRlc2VyaWFsaXplKHcpO1xufSJdfQ==