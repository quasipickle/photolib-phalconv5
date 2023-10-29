/* global Alpine */
import { docOn } from "./on.js";
import { $ } from "./selector.js";

docOn("alpine:init", () => {
    Alpine.data("gridRatio", function() {
        return {
            ratio: this.$persist("natural"),
            $grid: $(".album__grid"),

            init() {
                if(this.ratio == "square")
                    this.setRatio("square");
            },
            setRatio: function(newRatio) {
                this.ratio = newRatio;

                if (newRatio == "square") {
                    this.$grid.classList.add("album__grid--square");
                } else {
                    this.$grid.classList.remove("album__grid--square");
                }
            }
        };
    });
});