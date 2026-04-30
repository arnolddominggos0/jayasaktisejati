<div
    x-data
    x-show="$store.sidebar?.isOpen"
    x-transition.opacity
    x-cloak
    @click="$store.sidebar.close()"
    class="fi-sidebar-close-overlay fixed inset-0 z-[70] bg-slate-900/40">
</div>