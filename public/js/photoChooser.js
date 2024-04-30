/* global Alpine, bootstrap */
import { docOn } from "./on.js";

docOn("alpine:init", () => {
    Alpine.data("photoChooser", function() {
        return {
            modal: null,

            init(){
                this.modal = bootstrap.Modal.getOrCreateInstance(this.$el, {
                    backdrop:"static",
                    keyboard: false
                });
                window.addEventListener("photochooser:show", () => {
                    this.modal.show();
                });
            },
            choose(replacingPhotoId){
                if(confirm("Are you sure you want to replace the target photo with that photo?\n\nBattle stats & upload date will be replaced, original filename will not."))
                {
                    this.$dispatch("photochooser:choose", replacingPhotoId);
                    this.close();
                }
            },
            cancel() {
                this.close();
                this.$dispatch("photochooser:cancel");
            },
            close(){
                this.modal.hide();
            }
        };
    });
});