/* global Alpine, Sortable */

import { docOn } from "./on.js";
import { $,$$ } from "./selector.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("reorder", function() {
        return {
            _Sortable: null,
            $grid: $(".album__grid"),
            orderChanged: false,
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
                this._Sortable = Sortable.create(this.$grid,{
                    filter:".grid__item:has(.grid__album)",
                    multiDrag:true,
                    selectedClass: "sortable-selected",
                    onUpdate: () => {
                        this.orderChanged = true;
                    }
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
                this._Sortable.option("disabled", true);
                this.$store.sorting = false;
            },
            // store the new order in the DOM attributes
            storeOrder(){
                const $$sortable = $$(".grid__item[data-manual]", this.$grid);
                let counter = 0;
                $$sortable.forEach(item => {
                    item.dataset.manual = counter++;
                });
            }
        };
    });
});