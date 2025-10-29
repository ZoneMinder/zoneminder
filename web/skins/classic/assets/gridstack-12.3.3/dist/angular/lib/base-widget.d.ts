import { NgCompInputs, NgGridStackWidget } from './types';
import * as i0 from "@angular/core";
/**
 * Base widget class for GridStack Angular integration.
 */
export declare abstract class BaseWidget {
    /**
     * Complete widget definition including position, size, and Angular-specific data.
     * Populated automatically when the widget is loaded or saved.
     */
    widgetItem?: NgGridStackWidget;
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
    serialize(): NgCompInputs | undefined;
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
    deserialize(w: NgGridStackWidget): void;
    static ɵfac: i0.ɵɵFactoryDeclaration<BaseWidget, never>;
    static ɵprov: i0.ɵɵInjectableDeclaration<BaseWidget>;
}
