/* global Alpine */
import { docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("albumDelete", function() {
        return {
            // not "delete" because that's a reserved word
            deleteAlbum(){
                post("/album/delete", { albumId: window.albumId }, "delete an album")
                    .then(() => {
                        window.location.href = `${window.webRootPath}/album/${window.parentAlbumId}`;
                    });
            }
        };
    });
});