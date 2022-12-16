import { docOnLoad } from "./on.js";
import { $, $$ } from "./selector.js";

docOnLoad(() => {
    new bootstrap.Popover($("body"), {
        sanitize: false,
        placement: "bottom",
        content: window.popover,
        customClass: "grid__popover",
        trigger:"hover",
        selector: ".grid__info"
    });
});

function popover()
{
    const battlesInt = parseInt(this.dataset.battles);
    let winPercentageInt = parseInt(this.dataset.winPercentage);
    winPercentageInt = isNaN(winPercentageInt) ? 0 : winPercentageInt;
    const lossPercentageInt = 100 - winPercentageInt;

    let info = `
        <div class="d-flex justify-content-between">
            <span>${this.dataset.width} &times; ${this.dataset.height }</span>
            <span>${this.dataset.filesize}</span>
        </div>
    `;
    if (battlesInt > 0) {
        info += `
        <div class="progress flex-grow-1 grid__battle-bar">
            <div class="progress-bar bg-success" role="progressbar" aria-label="wins" style="width: ${winPercentageInt}%" aria-valuenow="${winPercentageInt}" aria-valuemin="0" aria-valuemax="100"></div>
            <div class="progress-bar bg-danger" role="progressbar" aria-label="losses" style="width: ${lossPercentageInt}%" aria-valuenow="${lossPercentageInt}" aria-valuemin="0" aria-valuemax="100"></div>
            <div class="grid__battle-stats">
                ${winPercentageInt}% / ${battlesInt}
            </div>
        </div>
        `;
    }

    return info;
}
window.popover = popover;