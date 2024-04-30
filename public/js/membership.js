/* global Alpine */

import { $ } from "./selector.js";
import { on, off,  docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("membership", function(photoIdParam) {
        return {
            albumChooseListener: null,
            albumCancelListener: null,
            photoChooseListener: null,
            photoCancelListener: null,
            replaceTargetPhotoId: null,
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
                this.albumChooseListener = on(window, "albumchooser:choose", this.move.bind(this));
                this.albumCancelListener = on(window,"albumchooser:cancel", this.removeAlbumListeners.bind(this));
            },
            startAdd()
            {
                this.$dispatch("albumchooser:show");
                this.albumChooseListener = on(window, "albumchooser:choose", this.add.bind(this));
                this.albumCancelListener = on(window,"albumchooser:cancel", this.removeAlbumListeners.bind(this));
            },
            startReplace(targetPhotoId)
            {
                this.replaceTargetPhotoId = targetPhotoId;
                this.$dispatch("photochooser:show");
                this.photoChooseListener = on(window,"photochooser:choose", this.replace.bind(this));
                this.photoCancelListener = on(window, "photochooser:cancel", this.removePhotoListeners.bind(this));
            },
            replace(chooseEvent)
            {
                this.removePhotoListeners();
                const data = {
                    targetPhotoId: photoIdParam,
                    replacingPhotoId: chooseEvent.detail
                };
                console.log(this.$el);

                post("/photo/replace", data, "replace a photo")
                    .then(()=> {
                        const $replacerItem = $("#photo-" + data.replacingPhotoId);
                        const $replacerPhoto = $("img", $replacerItem);

                        const $targetPhoto = $("img", this.$el.closest(".grid__item"));
                        $targetPhoto.src = $replacerPhoto.src;

                        $replacerItem.remove();
                    });
            },
            removeAlbumListeners(){
                off(window, "albumchooser:choose", this.albumChooseListener);
                off(window, "albumchooser:cancel", this.albumCancelListener);
            },
            removePhotoListeners(){
                off(window, "photochooser:choose", this.photoChooseListener);
                off(window, "photochooser:cancel", this.photoCancelListener);
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
                this.removeAlbumListeners();
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
                this.removeAlbumListeners();
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