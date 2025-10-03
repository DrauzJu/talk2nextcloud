/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    // biome-ignore lint: Needed for Vue components
    const component: DefineComponent<{}, {}, any>;
    export default component;
}
