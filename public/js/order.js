import { docOn } from "./on.js";
import { $, $$ } from "./selector.js";

docOn("alpine:init", () => {
    Alpine.store("albumOrder", {
        current: Alpine.$persist('manual')
    });

    Alpine.data("order", () => {
        return {
            orderTypes: {
                'manual':'Manual',
                'rank':'Rank',
                'date':'Date'
            },
            init(){
                if(Alpine.store('albumOrder').current != "manual") {
                    this.order(Alpine.store('albumOrder').current);
                }
            },
            order(by){
                const $grid = $(".album__grid");
                const $$items = $$(".grid__item", $grid);

                const sortable = [];
                $$items.forEach(item => {
                    if(item.dataset[by] != undefined) {
                        sortable.push(item);
                    }
                });

                if(by == 'manual') {
                    sortable.sort((a, b) => {
                        const manualA = a.dataset.manual;
                        const manualB = b.dataset.manual;

                        return manualA == manualB 
                            ? this.orderByDate(a, b)
                            : manualA - manualB;
                    });
                } else if (by == 'date') {
                    sortable.sort((a, b) => this.orderByDate(a, b));
                } else if (by == 'rank') {
                    sortable.sort((a, b) => {
                        const rankA = a.dataset.rank;
                        const rankB = b.dataset.rank;
                        return rankA == rankB 
                            ? this.orderByDate(a, b)
                            : rankB - rankA;
                    });
                }

                sortable.forEach(item => $grid.appendChild(item));
                Alpine.store('albumOrder').current = by;
            },

            orderByDate(a, b){
                const dateA = new Date(a.dataset.date);
                const dateB = new Date(b.dataset.date);
                return dateB - dateA;
            }
        };
    });
});