/* global Alpine */
import { docOn } from "./on.js";
import { post } from "./axios-wrapper.js";
import { $ } from "./selector.js";

docOn("alpine:init", () => {
    Alpine.data("photoFeature", (itemIdParam = 0, albumIdParam = 0, photoIdParam = 0) => ({
        itemId: itemIdParam,
        photoId: photoIdParam,
        albumId: albumIdParam,
        feature() {
            const data = {
                albumId: this.albumId,
                photoId: this.photoId
            };
            
            feature(data, "featuring a photo", this.itemId);
        }
    }));

    Alpine.data("albumFeature", (itemIdParam = 0, parentAlbumIdParam = 0) => ({
        itemId: itemIdParam,
        parentAlbumId: parentAlbumIdParam,
        feature(photoId) {
            const data = {
                albumId: this.parentAlbumId,
                photoId: photoId
            };
            
            feature(data, "featuring an album's featured photo", this.itemId);
        }
    }));
});

const feature = (data, task, itemId) => {
    post("/feature", data, task)
        .then(() => {
            const newFeaturedItem = $(`#${itemId}`);
            const oldFeaturedItem = $(".grid__item--featured");

            oldFeaturedItem?.classList.remove("grid__item--featured");
            newFeaturedItem?.classList.add("grid__item--featured");
        });
};