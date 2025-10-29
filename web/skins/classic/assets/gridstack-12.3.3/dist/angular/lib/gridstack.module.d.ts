import * as i0 from "@angular/core";
import * as i1 from "./gridstack-item.component";
import * as i2 from "./gridstack.component";
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
export declare class GridstackModule {
    static ɵfac: i0.ɵɵFactoryDeclaration<GridstackModule, never>;
    static ɵmod: i0.ɵɵNgModuleDeclaration<GridstackModule, never, [typeof i1.GridstackItemComponent, typeof i2.GridstackComponent], [typeof i1.GridstackItemComponent, typeof i2.GridstackComponent]>;
    static ɵinj: i0.ɵɵInjectorDeclaration<GridstackModule>;
}
