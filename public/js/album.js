/* global Alpine */
import "./albumChooser.js";
import "./albumDelete.js";
import "./albumMove.js";
import "./albumNew.js";
import "./albumRename.js";
import "./feature.js";
import "./filter.js";
import "./gridSize.js";
import "./gridRatio.js";
import "./lightbox.js";
import "./membership.js";
import "./photo-info.js";
import "./photoChooser.js";
import "./reorder.js";
import "./slideshow.js";
import "./upload.js";

import { $ } from "./selector.js";
import { docOn, docOnLoad } from "./on.js";

docOnLoad(() => {
    $(".sidebar__list-group .list-group-item.active")?.scrollIntoView();
});
docOn("alpine:init", () => {
    Alpine.store("lastAlbum", {
        album: Alpine.$persist(false)
    });
    Alpine.store("sorting", false);
});