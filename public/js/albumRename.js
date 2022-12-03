import { docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("albumRename", function() {
        return {
            start(currentAlbumName, currentAlbumId) {
                const newName = prompt(`Please enter the new name for "${currentAlbumName}"`);
                if(newName == "") {
                    alert("You must provide a name.");
                    this.start();
                }
                if(newName == null)
                    return;

                const data = {
                    id: currentAlbumId,
                    name: newName
                };
                post("/album/rename", data, "rename the album")
                    .then(data => {
                        window.location.reload();
                    });
            }
        }
    });
});