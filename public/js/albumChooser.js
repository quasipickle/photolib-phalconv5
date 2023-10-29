/* global Alpine, bootstrap */
import { docOn } from "./on.js";
import { get } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("albumChooser", function(albumIdParam) {
        return {
            modal: null,
            modalShown: false,
            albums: [],
            Album: null,
            Parent: null,
            options: {},

            init(){
                this.modal = bootstrap.Modal.getOrCreateInstance(this.$el, {
                    backdrop:"static",
                    keyboard: false
                });
                window.addEventListener("albumchooser:show", e => {
                    this.options = e.detail;    
                    this.loadAlbum(albumIdParam);
                });
            },
            loadAlbum(albumId) {
                get(`/chooser/${albumId}`, this.options, "load an album for the chooser")
                    .then(data => {
                        this.Album = data.album;
                        this.albums = data.albums;
                        this.Parent = data.parentAlbum;
                        if (!this.modalShown) {
                            this.modal.show();
                            this.modalShown = true;
                        }
                    });
            },
            choose(album) {
                this.close();
                this.$dispatch("albumchooser:choose", album);
            },
            cancel() {
                this.close();
                this.$dispatch("albumchooser:cancel");
            },
            close(){
                this.modal.hide();
                this.modalShown = false;
            }
        };
    });
});