/**
 * gridstack.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
import { AfterContentInit, ElementRef, EventEmitter, OnDestroy, OnInit, QueryList, Type, ViewContainerRef, ComponentRef } from '@angular/core';
import { Subscription } from 'rxjs';
import { GridHTMLElement, GridItemHTMLElement, GridStack, GridStackNode, GridStackOptions } from 'gridstack';
import { NgGridStackNode, NgGridStackWidget } from './types';
import { GridstackItemComponent } from './gridstack-item.component';
import * as i0 from "@angular/core";
/**
 * Event handler callback signatures for different GridStack events.
 * These types define the structure of data passed to Angular event emitters.
 */
/** Callback for general events (enable, disable, etc.) */
export declare type eventCB = {
    event: Event;
};
/** Callback for element-specific events (resize, drag, etc.) */
export declare type elementCB = {
    event: Event;
    el: GridItemHTMLElement;
};
/** Callback for events affecting multiple nodes (change, etc.) */
export declare type nodesCB = {
    event: Event;
    nodes: GridStackNode[];
};
/** Callback for drop events with before/after node state */
export declare type droppedCB = {
    event: Event;
    previousNode: GridStackNode;
    newNode: GridStackNode;
};
/**
 * Extended HTMLElement interface for the grid container.
 * Stores a back-reference to the Angular component for integration purposes.
 */
export interface GridCompHTMLElement extends GridHTMLElement {
    /** Back-reference to the Angular GridStack component */
    _gridComp?: GridstackComponent;
}
/**
 * Mapping of selector strings to Angular component types.
 * Used for dynamic component creation based on widget selectors.
 */
export declare type SelectorToType = {
    [key: string]: Type<Object>;
};
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
export declare class GridstackComponent implements OnInit, AfterContentInit, OnDestroy {
    protected readonly elementRef: ElementRef<GridCompHTMLElement>;
    /**
     * List of template-based grid items (not recommended approach).
     * Used to sync between DOM and GridStack internals when items are defined in templates.
     * Prefer dynamic component creation instead.
     */
    gridstackItems?: QueryList<GridstackItemComponent>;
    /**
     * Container for dynamic component creation (recommended approach).
     * Used to append grid items programmatically at runtime.
     */
    container?: ViewContainerRef;
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
    set options(o: GridStackOptions);
    /** Get the current running grid options */
    get options(): GridStackOptions;
    /**
     * Controls whether empty content should be displayed.
     * Set to true to show ng-content with 'empty-content' selector when grid has no items.
     *
     * @example
     * ```html
     * <gridstack [isEmpty]="gridItems.length === 0">
     *   <div empty-content>Drag widgets here to get started</div>
     * </gridstack>
     * ```
     */
    isEmpty?: boolean;
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
    addedCB: EventEmitter<nodesCB>;
    /** Emitted when grid layout changes */
    changeCB: EventEmitter<nodesCB>;
    /** Emitted when grid is disabled */
    disableCB: EventEmitter<eventCB>;
    /** Emitted during widget drag operations */
    dragCB: EventEmitter<elementCB>;
    /** Emitted when widget drag starts */
    dragStartCB: EventEmitter<elementCB>;
    /** Emitted when widget drag stops */
    dragStopCB: EventEmitter<elementCB>;
    /** Emitted when widget is dropped */
    droppedCB: EventEmitter<droppedCB>;
    /** Emitted when grid is enabled */
    enableCB: EventEmitter<eventCB>;
    /** Emitted when widgets are removed from the grid */
    removedCB: EventEmitter<nodesCB>;
    /** Emitted during widget resize operations */
    resizeCB: EventEmitter<elementCB>;
    /** Emitted when widget resize starts */
    resizeStartCB: EventEmitter<elementCB>;
    /** Emitted when widget resize stops */
    resizeStopCB: EventEmitter<elementCB>;
    /**
     * Get the native DOM element that contains grid-specific fields.
     * This element has GridStack properties attached to it.
     */
    get el(): GridCompHTMLElement;
    /**
     * Get the underlying GridStack instance.
     * Use this to access GridStack API methods directly.
     *
     * @example
     * ```typescript
     * this.gridComponent.grid.addWidget({x: 0, y: 0, w: 2, h: 1});
     * ```
     */
    get grid(): GridStack | undefined;
    /**
     * Component reference for dynamic component removal.
     * Used internally when this component is created dynamically.
     */
    ref: ComponentRef<GridstackComponent> | undefined;
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
    static selectorToType: SelectorToType;
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
    static addComponentToSelectorType(typeList: Array<Type<Object>>): void;
    /**
     * Extract the selector string from an Angular component type.
     *
     * @param type The component type to get selector from
     * @returns The component's selector string
     */
    static getSelector(type: Type<Object>): string;
    protected _options?: GridStackOptions;
    protected _grid?: GridStack;
    protected _sub: Subscription | undefined;
    protected loaded?: boolean;
    constructor(elementRef: ElementRef<GridCompHTMLElement>);
    ngOnInit(): void;
    /** wait until after all DOM is ready to init gridstack children (after angular ngFor and sub-components run first) */
    ngAfterContentInit(): void;
    ngOnDestroy(): void;
    /**
     * called when the TEMPLATE (not recommended) list of items changes - get a list of nodes and
     * update the layout accordingly (which will take care of adding/removing items changed by Angular)
     */
    updateAll(): void;
    /** check if the grid is empty, if so show alternative content */
    checkEmpty(): void;
    /** get all known events as easy to use Outputs for convenience */
    protected hookEvents(grid?: GridStack): void;
    protected unhookEvents(grid?: GridStack): void;
    static ɵfac: i0.ɵɵFactoryDeclaration<GridstackComponent, never>;
    static ɵcmp: i0.ɵɵComponentDeclaration<GridstackComponent, "gridstack", never, { "options": "options"; "isEmpty": "isEmpty"; }, { "addedCB": "addedCB"; "changeCB": "changeCB"; "disableCB": "disableCB"; "dragCB": "dragCB"; "dragStartCB": "dragStartCB"; "dragStopCB": "dragStopCB"; "droppedCB": "droppedCB"; "enableCB": "enableCB"; "removedCB": "removedCB"; "resizeCB": "resizeCB"; "resizeStartCB": "resizeStartCB"; "resizeStopCB": "resizeStopCB"; }, ["gridstackItems"], ["[empty-content]", "*"], true>;
}
/**
 * can be used when a new item needs to be created, which we do as a Angular component, or deleted (skip)
 **/
export declare function gsCreateNgComponents(host: GridCompHTMLElement | HTMLElement, n: NgGridStackNode, add: boolean, isGrid: boolean): HTMLElement | undefined;
/**
 * called for each item in the grid - check if additional information needs to be saved.
 * Note: since this is options minus gridstack protected members using Utils.removeInternalForSave(),
 * this typically doesn't need to do anything. However your custom Component @Input() are now supported
 * using BaseWidget.serialize()
 */
export declare function gsSaveAdditionalNgInfo(n: NgGridStackNode, w: NgGridStackWidget): void;
/**
 * track when widgeta re updated (rather than created) to make sure we de-serialize them as well
 */
export declare function gsUpdateNgComponents(n: NgGridStackNode): void;
