/* global Alpine */

import { on, off,  docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("membership", function(photoIdParam) {
        return {
            chooseListener: null,
            cancelListener: null,
            action: null,

            hasLastAlbum(){
                return this.$store.lastAlbum.album.name ?? null != null;
            },
            getLastAlbum(){
                return this.$store.lastAlbum.album;
            },
            startMove()
            {
                this.$dispatch("albumchooser:show");
                this.chooseListener = on(window, "albumchooser:choose", this.move.bind(this));
                this.cancelListener = on(window,"albumchooser:cancel", this.removeListeners.bind(this));
            },
            startAdd()
            {
                this.$dispatch("albumchooser:show");
                this.chooseListener = on(window, "albumchooser:choose", this.add.bind(this));
                this.cancelListener = on(window,"albumchooser:cancel", this.removeListeners.bind(this));
            },
            removeListeners(){
                off(window, "albumchooser:choose", this.chooseListener);
                off(window, "albumchooser:cancel", this.cancelListener);
            },
            move(chooseEvent)
            {
                this._move(chooseEvent.detail);
            },
            moveToLast(){
                this._move(this.$store.lastAlbum.album);
            },
            
            _move(album)
            {
                this.removeListeners();
                this.$store.lastAlbum.album = album;
                const data = {
                    photoId: photoIdParam,
                    albumId: album.id
                };

                post("/membership/move", data, "move a photo")
                    .then(()=> this.$el.closest(".grid__item").remove());
            },
            add(chooseEvent)
            {
                this._add(chooseEvent.detail);
            },
            addToLast(){
                this._add(this.$store.lastAlbum.album);
            },
            _add(album)
            {
                this.removeListeners();
                this.$store.lastAlbum.album = album;
                const data = {
                    photoId: photoIdParam,
                    albumId: album.id
                };

                post("/membership/add", data, "add a photo to another album");
            },
            remove(albumId){
                const data = {
                    photoId: photoIdParam,
                    albumId: albumId,
                };

                post("/membership/remove", data, "remove a photo from the album")
                    .then(()=> this.$el.closest(".grid__item").remove());
            },            
            // not "delete" because "delete" is a reserved word
            deletePhoto(){
                const data = { photoId: photoIdParam };
                post("/membership/delete", data, "delete a photo")
                    .then(()=> this.$el.closest(".grid__item").remove());
            },           
        };
    });
});