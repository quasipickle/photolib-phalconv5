import { LoupeManager } from "./loupe.js";
import { docOn, docOnLoad } from "./on.js";
import { $, $$ } from "./selector.js";

docOnLoad(() => {
    docOn("click", e => {
        if (e.target.classList.contains("lightboxable") && Alpine.store("sorting") != true) {
            const event = new CustomEvent("lightboxable-clicked", { detail: e.target });
            window.dispatchEvent(event);
        }
    });
});

docOn("alpine:init", () => {
    const loupeManager = new LoupeManager();

    Alpine.data("lightbox", () => ({
        photo: null,
        previous: null,
        next: null,
        show: false,
        box: function($clicked){
            this.photo = $clicked;
            this.previous = getPhotoEl($clicked.closest(".grid__item").previousElementSibling);
            this.next = getPhotoEl($clicked.closest(".grid__item").nextElementSibling);
            this.show = true;
            this.$nextTick(() => loupeManager.setCollection($$(".loupe-widget")));
        },
    }));
});

function getPhotoEl($node)
{
    if($node != null) {
        const $photo = $(".lightboxable", $node);
        if($photo != null) {
            return $photo;
        }
    }
    return null;
}