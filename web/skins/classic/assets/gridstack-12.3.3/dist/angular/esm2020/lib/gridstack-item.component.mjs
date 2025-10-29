/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
import { Component, Input, ViewChild, ViewContainerRef } from '@angular/core';
import * as i0 from "@angular/core";
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
export class GridstackItemComponent {
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZ3JpZHN0YWNrLWl0ZW0uY29tcG9uZW50LmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiLi4vLi4vLi4vLi4vYW5ndWxhci9wcm9qZWN0cy9saWIvc3JjL2xpYi9ncmlkc3RhY2staXRlbS5jb21wb25lbnQudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7OztHQUdHO0FBRUgsT0FBTyxFQUFFLFNBQVMsRUFBYyxLQUFLLEVBQUUsU0FBUyxFQUFFLGdCQUFnQixFQUEyQixNQUFNLGVBQWUsQ0FBQzs7QUFhbkg7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7R0FtQkc7QUFrQkgsTUFBTSxPQUFPLHNCQUFzQjtJQTBEakMsWUFBK0IsVUFBK0M7UUFBL0MsZUFBVSxHQUFWLFVBQVUsQ0FBcUM7UUFDNUUsSUFBSSxDQUFDLEVBQUUsQ0FBQyxhQUFhLEdBQUcsSUFBSSxDQUFDO0lBQy9CLENBQUM7SUF4Q0Q7Ozs7Ozs7Ozs7OztPQVlHO0lBQ0gsSUFBb0IsT0FBTyxDQUFDLEdBQWtCO1FBQzVDLE1BQU0sSUFBSSxHQUFHLElBQUksQ0FBQyxFQUFFLENBQUMsYUFBYSxFQUFFLElBQUksQ0FBQztRQUN6QyxJQUFJLElBQUksRUFBRTtZQUNSLGlDQUFpQztZQUNqQyxJQUFJLENBQUMsTUFBTSxDQUFDLElBQUksQ0FBQyxFQUFFLEVBQUUsR0FBRyxDQUFDLENBQUM7U0FDM0I7YUFBTTtZQUNMLDJGQUEyRjtZQUMzRixJQUFJLENBQUMsUUFBUSxHQUFHLEVBQUMsR0FBRyxHQUFHLEVBQUUsRUFBRSxFQUFFLElBQUksQ0FBQyxFQUFFLEVBQUMsQ0FBQztTQUN2QztJQUNILENBQUM7SUFDRCxvRkFBb0Y7SUFDcEYsSUFBVyxPQUFPO1FBQ2hCLE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxhQUFhLElBQUksSUFBSSxDQUFDLFFBQVEsSUFBSSxFQUFDLEVBQUUsRUFBRSxJQUFJLENBQUMsRUFBRSxFQUFDLENBQUM7SUFDakUsQ0FBQztJQUlELDJFQUEyRTtJQUMzRSxJQUFXLEVBQUUsS0FBOEIsT0FBTyxJQUFJLENBQUMsVUFBVSxDQUFDLGFBQWEsQ0FBQyxDQUFDLENBQUM7SUFFbEYsc0RBQXNEO0lBQy9DLFlBQVk7UUFDakIsT0FBTyxJQUFJLENBQUMsUUFBUSxDQUFDO0lBQ3ZCLENBQUM7SUFNTSxXQUFXO1FBQ2hCLElBQUksQ0FBQyxZQUFZLEVBQUUsQ0FBQztRQUNwQixPQUFPLElBQUksQ0FBQyxXQUFXLENBQUE7UUFDdkIsT0FBTyxJQUFJLENBQUMsRUFBRSxDQUFDLGFBQWEsQ0FBQztRQUM3QixPQUFPLElBQUksQ0FBQyxTQUFTLENBQUM7UUFDdEIsT0FBTyxJQUFJLENBQUMsR0FBRyxDQUFDO0lBQ2xCLENBQUM7O21IQXBFVSxzQkFBc0I7dUdBQXRCLHNCQUFzQiw2TEFNRCxnQkFBZ0IsMkNBckJ0Qzs7Ozs7Ozs7V0FRRDsyRkFPRSxzQkFBc0I7a0JBakJsQyxTQUFTOytCQUNFLGdCQUFnQixZQUNoQjs7Ozs7Ozs7V0FRRCxjQUlHLElBQUk7aUdBU3dELFNBQVM7c0JBQWhGLFNBQVM7dUJBQUMsV0FBVyxFQUFFLEVBQUUsSUFBSSxFQUFFLGdCQUFnQixFQUFFLE1BQU0sRUFBRSxJQUFJLEVBQUM7Z0JBMkIzQyxPQUFPO3NCQUExQixLQUFLIiwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBncmlkc3RhY2staXRlbS5jb21wb25lbnQudHMgMTIuMy4zXG4gKiBDb3B5cmlnaHQgKGMpIDIwMjItMjAyNCBBbGFpbiBEdW1lc255IC0gc2VlIEdyaWRTdGFjayByb290IGxpY2Vuc2VcbiAqL1xuXG5pbXBvcnQgeyBDb21wb25lbnQsIEVsZW1lbnRSZWYsIElucHV0LCBWaWV3Q2hpbGQsIFZpZXdDb250YWluZXJSZWYsIE9uRGVzdHJveSwgQ29tcG9uZW50UmVmIH0gZnJvbSAnQGFuZ3VsYXIvY29yZSc7XG5pbXBvcnQgeyBHcmlkSXRlbUhUTUxFbGVtZW50LCBHcmlkU3RhY2tOb2RlIH0gZnJvbSAnZ3JpZHN0YWNrJztcbmltcG9ydCB7IEJhc2VXaWRnZXQgfSBmcm9tICcuL2Jhc2Utd2lkZ2V0JztcblxuLyoqXG4gKiBFeHRlbmRlZCBIVE1MRWxlbWVudCBpbnRlcmZhY2UgZm9yIGdyaWQgaXRlbXMuXG4gKiBTdG9yZXMgYSBiYWNrLXJlZmVyZW5jZSB0byB0aGUgQW5ndWxhciBjb21wb25lbnQgZm9yIGludGVncmF0aW9uLlxuICovXG5leHBvcnQgaW50ZXJmYWNlIEdyaWRJdGVtQ29tcEhUTUxFbGVtZW50IGV4dGVuZHMgR3JpZEl0ZW1IVE1MRWxlbWVudCB7XG4gIC8qKiBCYWNrLXJlZmVyZW5jZSB0byB0aGUgQW5ndWxhciBHcmlkU3RhY2tJdGVtIGNvbXBvbmVudCAqL1xuICBfZ3JpZEl0ZW1Db21wPzogR3JpZHN0YWNrSXRlbUNvbXBvbmVudDtcbn1cblxuLyoqXG4gKiBBbmd1bGFyIGNvbXBvbmVudCB3cmFwcGVyIGZvciBpbmRpdmlkdWFsIEdyaWRTdGFjayBpdGVtcy5cbiAqIFxuICogVGhpcyBjb21wb25lbnQgcmVwcmVzZW50cyBhIHNpbmdsZSBncmlkIGl0ZW0gYW5kIGhhbmRsZXM6XG4gKiAtIER5bmFtaWMgY29udGVudCBjcmVhdGlvbiBhbmQgbWFuYWdlbWVudFxuICogLSBJbnRlZ3JhdGlvbiB3aXRoIHBhcmVudCBHcmlkU3RhY2sgY29tcG9uZW50XG4gKiAtIENvbXBvbmVudCBsaWZlY3ljbGUgYW5kIGNsZWFudXBcbiAqIC0gV2lkZ2V0IG9wdGlvbnMgYW5kIGNvbmZpZ3VyYXRpb25cbiAqIFxuICogVXNlIGluIGNvbWJpbmF0aW9uIHdpdGggR3JpZHN0YWNrQ29tcG9uZW50IGZvciB0aGUgcGFyZW50IGdyaWQuXG4gKiBcbiAqIEBleGFtcGxlXG4gKiBgYGBodG1sXG4gKiA8Z3JpZHN0YWNrPlxuICogICA8Z3JpZHN0YWNrLWl0ZW0gW29wdGlvbnNdPVwie3g6IDAsIHk6IDAsIHc6IDIsIGg6IDF9XCI+XG4gKiAgICAgPG15LXdpZGdldC1jb21wb25lbnQ+PC9teS13aWRnZXQtY29tcG9uZW50PlxuICogICA8L2dyaWRzdGFjay1pdGVtPlxuICogPC9ncmlkc3RhY2s+XG4gKiBgYGBcbiAqL1xuQENvbXBvbmVudCh7XG4gIHNlbGVjdG9yOiAnZ3JpZHN0YWNrLWl0ZW0nLFxuICB0ZW1wbGF0ZTogYFxuICAgIDxkaXYgY2xhc3M9XCJncmlkLXN0YWNrLWl0ZW0tY29udGVudFwiPlxuICAgICAgPCEtLSB3aGVyZSBkeW5hbWljIGl0ZW1zIGdvIGJhc2VkIG9uIGNvbXBvbmVudCBzZWxlY3RvciAocmVjb21tZW5kZWQgd2F5KSwgb3Igc3ViLWdyaWRzLCBldGMuLi4pIC0tPlxuICAgICAgPG5nLXRlbXBsYXRlICNjb250YWluZXI+PC9uZy10ZW1wbGF0ZT5cbiAgICAgIDwhLS0gYW55IHN0YXRpYyAoZGVmaW5lZCBpbiBET00gLSBub3QgcmVjb21tZW5kZWQpIGNvbnRlbnQgZ29lcyBoZXJlIC0tPlxuICAgICAgPG5nLWNvbnRlbnQ+PC9uZy1jb250ZW50PlxuICAgICAgPCEtLSBmYWxsYmFjayBIVE1MIGNvbnRlbnQgZnJvbSBHcmlkU3RhY2tXaWRnZXQuY29udGVudCBpZiB1c2VkIGluc3RlYWQgKG5vdCByZWNvbW1lbmRlZCkgLS0+XG4gICAgICB7e29wdGlvbnMuY29udGVudH19XG4gICAgPC9kaXY+YCxcbiAgc3R5bGVzOiBbYFxuICAgIDpob3N0IHsgZGlzcGxheTogYmxvY2s7IH1cbiAgYF0sXG4gIHN0YW5kYWxvbmU6IHRydWUsXG4gIC8vIGNoYW5nZURldGVjdGlvbjogQ2hhbmdlRGV0ZWN0aW9uU3RyYXRlZ3kuT25QdXNoLCAvLyBJRkYgeW91IHdhbnQgdG8gb3B0aW1pemUgYW5kIGNvbnRyb2wgd2hlbiBDaGFuZ2VEZXRlY3Rpb24gbmVlZHMgdG8gaGFwcGVuLi4uXG59KVxuZXhwb3J0IGNsYXNzIEdyaWRzdGFja0l0ZW1Db21wb25lbnQgaW1wbGVtZW50cyBPbkRlc3Ryb3kge1xuXG4gIC8qKlxuICAgKiBDb250YWluZXIgZm9yIGR5bmFtaWMgY29tcG9uZW50IGNyZWF0aW9uIHdpdGhpbiB0aGlzIGdyaWQgaXRlbS5cbiAgICogVXNlZCB0byBhcHBlbmQgY2hpbGQgY29tcG9uZW50cyBwcm9ncmFtbWF0aWNhbGx5LlxuICAgKi9cbiAgQFZpZXdDaGlsZCgnY29udGFpbmVyJywgeyByZWFkOiBWaWV3Q29udGFpbmVyUmVmLCBzdGF0aWM6IHRydWV9KSBwdWJsaWMgY29udGFpbmVyPzogVmlld0NvbnRhaW5lclJlZjtcblxuICAvKipcbiAgICogQ29tcG9uZW50IHJlZmVyZW5jZSBmb3IgZHluYW1pYyBjb21wb25lbnQgcmVtb3ZhbC5cbiAgICogVXNlZCBpbnRlcm5hbGx5IHdoZW4gdGhpcyBjb21wb25lbnQgaXMgY3JlYXRlZCBkeW5hbWljYWxseS5cbiAgICovXG4gIHB1YmxpYyByZWY6IENvbXBvbmVudFJlZjxHcmlkc3RhY2tJdGVtQ29tcG9uZW50PiB8IHVuZGVmaW5lZDtcblxuICAvKipcbiAgICogUmVmZXJlbmNlIHRvIGNoaWxkIHdpZGdldCBjb21wb25lbnQgZm9yIHNlcmlhbGl6YXRpb24uXG4gICAqIFVzZWQgdG8gc2F2ZS9yZXN0b3JlIGFkZGl0aW9uYWwgZGF0YSBhbG9uZyB3aXRoIGdyaWQgcG9zaXRpb24uXG4gICAqL1xuICBwdWJsaWMgY2hpbGRXaWRnZXQ6IEJhc2VXaWRnZXQgfCB1bmRlZmluZWQ7XG5cbiAgLyoqXG4gICAqIEdyaWQgaXRlbSBjb25maWd1cmF0aW9uIG9wdGlvbnMuXG4gICAqIERlZmluZXMgcG9zaXRpb24sIHNpemUsIGFuZCBiZWhhdmlvciBvZiB0aGlzIGdyaWQgaXRlbS5cbiAgICogXG4gICAqIEBleGFtcGxlXG4gICAqIGBgYHR5cGVzY3JpcHRcbiAgICogaXRlbU9wdGlvbnM6IEdyaWRTdGFja05vZGUgPSB7XG4gICAqICAgeDogMCwgeTogMCwgdzogMiwgaDogMSxcbiAgICogICBub1Jlc2l6ZTogdHJ1ZSxcbiAgICogICBjb250ZW50OiAnSXRlbSBjb250ZW50J1xuICAgKiB9O1xuICAgKiBgYGBcbiAgICovXG4gIEBJbnB1dCgpIHB1YmxpYyBzZXQgb3B0aW9ucyh2YWw6IEdyaWRTdGFja05vZGUpIHtcbiAgICBjb25zdCBncmlkID0gdGhpcy5lbC5ncmlkc3RhY2tOb2RlPy5ncmlkO1xuICAgIGlmIChncmlkKSB7XG4gICAgICAvLyBhbHJlYWR5IGJ1aWx0LCBkbyBhbiB1cGRhdGUuLi5cbiAgICAgIGdyaWQudXBkYXRlKHRoaXMuZWwsIHZhbCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIHN0b3JlIG91ciBjdXN0b20gZWxlbWVudCBpbiBvcHRpb25zIHNvIHdlIGNhbiB1cGRhdGUgaXQgYW5kIG5vdCByZS1jcmVhdGUgYSBnZW5lcmljIGRpdiFcbiAgICAgIHRoaXMuX29wdGlvbnMgPSB7Li4udmFsLCBlbDogdGhpcy5lbH07XG4gICAgfVxuICB9XG4gIC8qKiByZXR1cm4gdGhlIGxhdGVzdCBncmlkIG9wdGlvbnMgKGZyb20gR1Mgb25jZSBidWlsdCwgb3RoZXJ3aXNlIGluaXRpYWwgdmFsdWVzKSAqL1xuICBwdWJsaWMgZ2V0IG9wdGlvbnMoKTogR3JpZFN0YWNrTm9kZSB7XG4gICAgcmV0dXJuIHRoaXMuZWwuZ3JpZHN0YWNrTm9kZSB8fCB0aGlzLl9vcHRpb25zIHx8IHtlbDogdGhpcy5lbH07XG4gIH1cblxuICBwcm90ZWN0ZWQgX29wdGlvbnM/OiBHcmlkU3RhY2tOb2RlO1xuXG4gIC8qKiByZXR1cm4gdGhlIG5hdGl2ZSBlbGVtZW50IHRoYXQgY29udGFpbnMgZ3JpZCBzcGVjaWZpYyBmaWVsZHMgYXMgd2VsbCAqL1xuICBwdWJsaWMgZ2V0IGVsKCk6IEdyaWRJdGVtQ29tcEhUTUxFbGVtZW50IHsgcmV0dXJuIHRoaXMuZWxlbWVudFJlZi5uYXRpdmVFbGVtZW50OyB9XG5cbiAgLyoqIGNsZWFycyB0aGUgaW5pdGlhbCBvcHRpb25zIG5vdyB0aGF0IHdlJ3ZlIGJ1aWx0ICovXG4gIHB1YmxpYyBjbGVhck9wdGlvbnMoKSB7XG4gICAgZGVsZXRlIHRoaXMuX29wdGlvbnM7XG4gIH1cblxuICBjb25zdHJ1Y3Rvcihwcm90ZWN0ZWQgcmVhZG9ubHkgZWxlbWVudFJlZjogRWxlbWVudFJlZjxHcmlkSXRlbUNvbXBIVE1MRWxlbWVudD4pIHtcbiAgICB0aGlzLmVsLl9ncmlkSXRlbUNvbXAgPSB0aGlzO1xuICB9XG5cbiAgcHVibGljIG5nT25EZXN0cm95KCk6IHZvaWQge1xuICAgIHRoaXMuY2xlYXJPcHRpb25zKCk7XG4gICAgZGVsZXRlIHRoaXMuY2hpbGRXaWRnZXRcbiAgICBkZWxldGUgdGhpcy5lbC5fZ3JpZEl0ZW1Db21wO1xuICAgIGRlbGV0ZSB0aGlzLmNvbnRhaW5lcjtcbiAgICBkZWxldGUgdGhpcy5yZWY7XG4gIH1cbn1cbiJdfQ==