/* global Alpine */
import { on, off, docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("albumMove", function() {
        return {
            chooseListener: null,
            cancelListener: null,
            start() {
                this.chooseListener = on(window, "albumchooser:choose", this.move.bind(this));
                this.cancelListener = on(window,"albumchooser:cancel", this.removeListeners.bind(this));

                this.$dispatch("albumchooser:show",{ omit: [window.albumId] });
            },
            removeListeners(){
                off(window, "albumchooser:choose", this.chooseListener);
                off(window, "albumchooser:cancel", this.cancelListener);
            },
            move(chooseEvent)
            {
                this.removeListeners();
                const data = {
                    albumId: window.albumId,
                    parentId: chooseEvent.detail.id
                };

                post("/album/move", data, "move an album")
                    .then(()=> window.location.reload());
            }
        };
    });
});