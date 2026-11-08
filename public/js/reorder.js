/* global Alpine, Sortable */

import { docOn, docOff } from "./on.js";
import { $,$$ } from "./selector.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("reorder", function() {
        return {
            _Sortable: null,
            _SidebarSortable:null,
            $grid: $(".album__grid"),
            $sidebar: $(".sidebar__list-group"),
            $breadcrumb: $(".album__breadcrumb"),
            orderChanged: false,
            dragging: false,
            $dragTarget: null,
            pointerListener: null,
            init(){
                this.$watch("$store.sorting", () => {
                    if (this.$store.sorting) {
                        this.$grid.classList.add("album__grid--sorting");
                    } else {
                        this.$grid.classList.remove("album__grid--sorting");
                    }
                });
            },
            startReorder(){
                this.$store.sorting = true;
                this.$sidebar.classList.add("move-drop-target");
                this.$breadcrumb.classList.add("move-drop-target");
                this._Sortable = Sortable.create(this.$grid, {
                    filter:".grid__item:has(.grid__album)",
                    forceFallback: true,
                    group: {
                        name: "photos",
                        pull: true,
                        put: false,
                    },
                    multiDrag:true,
                    selectedClass: "sortable-selected",
                    onStart: () => {
                        this.dragging = true;
                        this.pointerListener ??= docOn("pointermove", this.onPointerMove.bind(this));
                    },
                    onUpdate: () => {
                        this.orderChanged = true;
                    },
                    onEnd: (evt) => {
                        const $$items = evt.items.length ? evt.items : [evt.item];
                        if(this.$dragTarget) {
                            this.saveMove(this.$dragTarget.dataset.id, $$items);
                        }
                        this.$dragTarget = null;
                        this.dragging = false;
                    }
                });
            },
            saveMove(albumId, $$photos){
                $$photos.forEach($photo => {
                    const data = {
                        albumId,
                        photoId: $photo.dataset.id
                        
                    };
                    post("/membership/move", data, "move photo")
                        .then(_ => {
                            $photo.remove();
                        });
                });
            },
            saveOrder(){
                const data = {
                    albumId: window.albumId,
                    "order[]": this._Sortable.toArray()
                };

                post("/album/order", data, "ordering photos");
                this.storeOrder();
                this.stopOrdering();
            },
            cancelOrder(){
                if (this.orderChanged) {
                    // Just reloading because it's easier than reverting the sort order
                    window.location.reload();
                } else {
                    this.stopOrdering();
                }
            },
            stopOrdering(){
                this.$sidebar.classList.remove('move-drop-target');
                this.$breadcrumb.classList.remove("move-drop-target");
                this._Sortable.option("disabled", true);
                this.$store.sorting = false;
                docOff('pointermove', this.pointerListener);
            },
            // store the new order in the DOM attributes
            storeOrder(){
                const $$sortable = $$(".grid__item[data-manual]", this.$grid);
                let counter = 0;
                $$sortable.forEach(item => {
                    item.dataset.manual = counter++;
                });
            },
            onPointerMove(evt){
                if(!this.dragging)
                    return;
                const $element = document.elementFromPoint(evt.clientX, evt.clientY);
                const $currentTarget = $element?.closest(".sidebar__list-item, .breadcrumb-item");

                if($currentTarget === this.$dragTarget)
                    return;
                
                this.$dragTarget?.classList.remove('move-drop-target');
                this.$dragTarget = $currentTarget;
                this.$dragTarget?.classList.add('move-drop-target');
            }
        };
    });
});