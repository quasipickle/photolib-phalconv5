import { $ } from "./selector.js";
import { docOnLoad } from "./on.js";
import "./lightbox.js";
import "./gridSize.js";
import "./gridRatio.js";
import "./photo-info.js";
import "./search.js";

docOnLoad(() => {
    $(".sidebar__list-group .list-group-item.active")?.scrollIntoView();
});