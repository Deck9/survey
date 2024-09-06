/// <reference types="vite/client" />

declare module "*.vue" {
    import { DefineComponent } from "vue";
    // eslint-disable-next-line @typescript-eslint/no-explicit-any, @typescript-eslint/no-empty-object-type
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

declare module "vue3-smooth-dnd" {
    import { DefineComponent } from "vue";

    export const Container: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        any
    >;
    export const Draggable: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        any
    >;
}
