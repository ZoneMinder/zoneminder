/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
import { ElementRef, ViewContainerRef, OnDestroy, ComponentRef } from '@angular/core';
import { GridItemHTMLElement, GridStackNode } from 'gridstack';
import { BaseWidget } from './base-widget';
import * as i0 from "@angular/core";
/**
 * Extended HTMLElement interface for grid items.
 * Stores a back-reference to the Angular component for integration.
 */
export interface GridItemCompHTMLElement extends GridItemHTMLElement {
    /** Back-reference to the Angular GridStackItem component */
    _gridItemComp?: GridstackItemComponent;
}
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
export declare class GridstackItemComponent implements OnDestroy {
    protected readonly elementRef: ElementRef<GridItemCompHTMLElement>;
    /**
     * Container for dynamic component creation within this grid item.
     * Used to append child components programmatically.
     */
    container?: ViewContainerRef;
    /**
     * Component reference for dynamic component removal.
     * Used internally when this component is created dynamically.
     */
    ref: ComponentRef<GridstackItemComponent> | undefined;
    /**
     * Reference to child widget component for serialization.
     * Used to save/restore additional data along with grid position.
     */
    childWidget: BaseWidget | undefined;
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
    set options(val: GridStackNode);
    /** return the latest grid options (from GS once built, otherwise initial values) */
    get options(): GridStackNode;
    protected _options?: GridStackNode;
    /** return the native element that contains grid specific fields as well */
    get el(): GridItemCompHTMLElement;
    /** clears the initial options now that we've built */
    clearOptions(): void;
    constructor(elementRef: ElementRef<GridItemCompHTMLElement>);
    ngOnDestroy(): void;
    static ɵfac: i0.ɵɵFactoryDeclaration<GridstackItemComponent, never>;
    static ɵcmp: i0.ɵɵComponentDeclaration<GridstackItemComponent, "gridstack-item", never, { "options": "options"; }, {}, never, ["*"], true>;
}
