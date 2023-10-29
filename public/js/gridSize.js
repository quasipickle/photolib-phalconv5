/* global Alpine */

import { docOn } from "./on.js";
import { $ } from "./selector.js";

docOn("alpine:init", () => {
    Alpine.data("gridSize", function() {
        return {
            size: this.$persist("lg"),
            $grid: $(".album__grid"),

            init() {
                if(this.size == "sm")
                    this.setSize("sm");
            },
            setSize: function(newSize) {
                this.size = newSize;

                if (newSize == "sm") {
                    this.$grid.classList.add("album__grid--sm");
                } else {
                    this.$grid.classList.remove("album__grid--sm");
                }
            }
        };
    });
});