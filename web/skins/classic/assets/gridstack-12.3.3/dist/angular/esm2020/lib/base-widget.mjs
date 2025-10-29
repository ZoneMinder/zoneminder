/**
 * gridstack-item.component.ts 12.3.3
 * Copyright (c) 2022-2024 Alain Dumesny - see GridStack root license
 */
/**
 * Abstract base class that all custom widgets should extend.
 *
 * This class provides the interface needed for GridstackItemComponent to:
 * - Serialize/deserialize widget data
 * - Save/restore widget state
 * - Integrate with Angular lifecycle
 *
 * Extend this class when creating custom widgets for dynamic grids.
 *
 * @example
 * ```typescript
 * @Component({
 *   selector: 'my-custom-widget',
 *   template: '<div>{{data}}</div>'
 * })
 * export class MyCustomWidget extends BaseWidget {
 *   @Input() data: string = '';
 *
 *   serialize() {
 *     return { data: this.data };
 *   }
 * }
 * ```
 */
import { Injectable } from '@angular/core';
import * as i0 from "@angular/core";
/**
 * Base widget class for GridStack Angular integration.
 */
export class BaseWidget {
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYmFzZS13aWRnZXQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyIuLi8uLi8uLi8uLi9hbmd1bGFyL3Byb2plY3RzL2xpYi9zcmMvbGliL2Jhc2Utd2lkZ2V0LnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBOzs7R0FHRztBQUVIOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7R0F3Qkc7QUFFSCxPQUFPLEVBQUUsVUFBVSxFQUFFLE1BQU0sZUFBZSxDQUFDOztBQUczQzs7R0FFRztBQUVILE1BQU0sT0FBZ0IsVUFBVTtJQVE5Qjs7Ozs7Ozs7Ozs7Ozs7Ozs7O09Ba0JHO0lBQ0ksU0FBUyxLQUFnQyxPQUFPLENBQUMsQ0FBQztJQUV6RDs7Ozs7Ozs7Ozs7Ozs7Ozs7OztPQW1CRztJQUNJLFdBQVcsQ0FBQyxDQUFvQjtRQUNyQyxzQ0FBc0M7UUFDdEMsSUFBSSxDQUFDLFVBQVUsR0FBRyxDQUFDLENBQUM7UUFDcEIsSUFBSSxDQUFDLENBQUM7WUFBRSxPQUFPO1FBRWYsSUFBSSxDQUFDLENBQUMsS0FBSztZQUFHLE1BQU0sQ0FBQyxNQUFNLENBQUMsSUFBSSxFQUFFLENBQUMsQ0FBQyxLQUFLLENBQUMsQ0FBQztJQUM3QyxDQUFDOzt1R0F2RG1CLFVBQVU7MkdBQVYsVUFBVTsyRkFBVixVQUFVO2tCQUQvQixVQUFVIiwic291cmNlc0NvbnRlbnQiOlsiLyoqXHJcbiAqIGdyaWRzdGFjay1pdGVtLmNvbXBvbmVudC50cyAxMi4zLjNcclxuICogQ29weXJpZ2h0IChjKSAyMDIyLTIwMjQgQWxhaW4gRHVtZXNueSAtIHNlZSBHcmlkU3RhY2sgcm9vdCBsaWNlbnNlXHJcbiAqL1xyXG5cclxuLyoqXHJcbiAqIEFic3RyYWN0IGJhc2UgY2xhc3MgdGhhdCBhbGwgY3VzdG9tIHdpZGdldHMgc2hvdWxkIGV4dGVuZC5cclxuICogXHJcbiAqIFRoaXMgY2xhc3MgcHJvdmlkZXMgdGhlIGludGVyZmFjZSBuZWVkZWQgZm9yIEdyaWRzdGFja0l0ZW1Db21wb25lbnQgdG86XHJcbiAqIC0gU2VyaWFsaXplL2Rlc2VyaWFsaXplIHdpZGdldCBkYXRhXHJcbiAqIC0gU2F2ZS9yZXN0b3JlIHdpZGdldCBzdGF0ZVxyXG4gKiAtIEludGVncmF0ZSB3aXRoIEFuZ3VsYXIgbGlmZWN5Y2xlXHJcbiAqIFxyXG4gKiBFeHRlbmQgdGhpcyBjbGFzcyB3aGVuIGNyZWF0aW5nIGN1c3RvbSB3aWRnZXRzIGZvciBkeW5hbWljIGdyaWRzLlxyXG4gKiBcclxuICogQGV4YW1wbGVcclxuICogYGBgdHlwZXNjcmlwdFxyXG4gKiBAQ29tcG9uZW50KHtcclxuICogICBzZWxlY3RvcjogJ215LWN1c3RvbS13aWRnZXQnLFxyXG4gKiAgIHRlbXBsYXRlOiAnPGRpdj57e2RhdGF9fTwvZGl2PidcclxuICogfSlcclxuICogZXhwb3J0IGNsYXNzIE15Q3VzdG9tV2lkZ2V0IGV4dGVuZHMgQmFzZVdpZGdldCB7XHJcbiAqICAgQElucHV0KCkgZGF0YTogc3RyaW5nID0gJyc7XHJcbiAqICAgXHJcbiAqICAgc2VyaWFsaXplKCkge1xyXG4gKiAgICAgcmV0dXJuIHsgZGF0YTogdGhpcy5kYXRhIH07XHJcbiAqICAgfVxyXG4gKiB9XHJcbiAqIGBgYFxyXG4gKi9cclxuXHJcbmltcG9ydCB7IEluamVjdGFibGUgfSBmcm9tICdAYW5ndWxhci9jb3JlJztcclxuaW1wb3J0IHsgTmdDb21wSW5wdXRzLCBOZ0dyaWRTdGFja1dpZGdldCB9IGZyb20gJy4vdHlwZXMnO1xyXG5cclxuLyoqXHJcbiAqIEJhc2Ugd2lkZ2V0IGNsYXNzIGZvciBHcmlkU3RhY2sgQW5ndWxhciBpbnRlZ3JhdGlvbi5cclxuICovXHJcbkBJbmplY3RhYmxlKClcclxuZXhwb3J0IGFic3RyYWN0IGNsYXNzIEJhc2VXaWRnZXQge1xyXG5cclxuICAvKipcclxuICAgKiBDb21wbGV0ZSB3aWRnZXQgZGVmaW5pdGlvbiBpbmNsdWRpbmcgcG9zaXRpb24sIHNpemUsIGFuZCBBbmd1bGFyLXNwZWNpZmljIGRhdGEuXHJcbiAgICogUG9wdWxhdGVkIGF1dG9tYXRpY2FsbHkgd2hlbiB0aGUgd2lkZ2V0IGlzIGxvYWRlZCBvciBzYXZlZC5cclxuICAgKi9cclxuICBwdWJsaWMgd2lkZ2V0SXRlbT86IE5nR3JpZFN0YWNrV2lkZ2V0O1xyXG5cclxuICAvKipcclxuICAgKiBPdmVycmlkZSB0aGlzIG1ldGhvZCB0byByZXR1cm4gc2VyaWFsaXphYmxlIGRhdGEgZm9yIHRoaXMgd2lkZ2V0LlxyXG4gICAqIFxyXG4gICAqIFJldHVybiBhbiBvYmplY3Qgd2l0aCBwcm9wZXJ0aWVzIHRoYXQgbWFwIHRvIHlvdXIgY29tcG9uZW50J3MgQElucHV0KCkgZmllbGRzLlxyXG4gICAqIFRoZSBzZWxlY3RvciBpcyBoYW5kbGVkIGF1dG9tYXRpY2FsbHksIHNvIG9ubHkgaW5jbHVkZSBjb21wb25lbnQtc3BlY2lmaWMgZGF0YS5cclxuICAgKiBcclxuICAgKiBAcmV0dXJucyBPYmplY3QgY29udGFpbmluZyBzZXJpYWxpemFibGUgY29tcG9uZW50IGRhdGFcclxuICAgKiBcclxuICAgKiBAZXhhbXBsZVxyXG4gICAqIGBgYHR5cGVzY3JpcHRcclxuICAgKiBzZXJpYWxpemUoKSB7XHJcbiAgICogICByZXR1cm4ge1xyXG4gICAqICAgICB0aXRsZTogdGhpcy50aXRsZSxcclxuICAgKiAgICAgdmFsdWU6IHRoaXMudmFsdWUsXHJcbiAgICogICAgIHNldHRpbmdzOiB0aGlzLnNldHRpbmdzXHJcbiAgICogICB9O1xyXG4gICAqIH1cclxuICAgKiBgYGBcclxuICAgKi9cclxuICBwdWJsaWMgc2VyaWFsaXplKCk6IE5nQ29tcElucHV0cyB8IHVuZGVmaW5lZCAgeyByZXR1cm47IH1cclxuXHJcbiAgLyoqXHJcbiAgICogT3ZlcnJpZGUgdGhpcyBtZXRob2QgdG8gaGFuZGxlIHdpZGdldCByZXN0b3JhdGlvbiBmcm9tIHNhdmVkIGRhdGEuXHJcbiAgICogXHJcbiAgICogVXNlIHRoaXMgZm9yIGNvbXBsZXggaW5pdGlhbGl6YXRpb24gdGhhdCBnb2VzIGJleW9uZCBzaW1wbGUgQElucHV0KCkgbWFwcGluZy5cclxuICAgKiBUaGUgZGVmYXVsdCBpbXBsZW1lbnRhdGlvbiBhdXRvbWF0aWNhbGx5IGFzc2lnbnMgaW5wdXQgZGF0YSB0byBjb21wb25lbnQgcHJvcGVydGllcy5cclxuICAgKiBcclxuICAgKiBAcGFyYW0gdyBUaGUgc2F2ZWQgd2lkZ2V0IGRhdGEgaW5jbHVkaW5nIGlucHV0IHByb3BlcnRpZXNcclxuICAgKiBcclxuICAgKiBAZXhhbXBsZVxyXG4gICAqIGBgYHR5cGVzY3JpcHRcclxuICAgKiBkZXNlcmlhbGl6ZSh3OiBOZ0dyaWRTdGFja1dpZGdldCkge1xyXG4gICAqICAgc3VwZXIuZGVzZXJpYWxpemUodyk7IC8vIENhbGwgcGFyZW50IGZvciBiYXNpYyBzZXR1cFxyXG4gICAqICAgXHJcbiAgICogICAvLyBDdXN0b20gaW5pdGlhbGl6YXRpb24gbG9naWNcclxuICAgKiAgIGlmICh3LmlucHV0Py5jb21wbGV4RGF0YSkge1xyXG4gICAqICAgICB0aGlzLnByb2Nlc3NDb21wbGV4RGF0YSh3LmlucHV0LmNvbXBsZXhEYXRhKTtcclxuICAgKiAgIH1cclxuICAgKiB9XHJcbiAgICogYGBgXHJcbiAgICovXHJcbiAgcHVibGljIGRlc2VyaWFsaXplKHc6IE5nR3JpZFN0YWNrV2lkZ2V0KSAge1xyXG4gICAgLy8gc2F2ZSBmdWxsIGRlc2NyaXB0aW9uIGZvciBtZXRhIGRhdGFcclxuICAgIHRoaXMud2lkZ2V0SXRlbSA9IHc7XHJcbiAgICBpZiAoIXcpIHJldHVybjtcclxuXHJcbiAgICBpZiAody5pbnB1dCkgIE9iamVjdC5hc3NpZ24odGhpcywgdy5pbnB1dCk7XHJcbiAgfVxyXG4gfVxyXG4iXX0=