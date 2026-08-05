const grid = document.getElementById("grid");
const TILE_SIZE = 50; 

function createGrid() {
    grid.innerHTML = ""; 

    const columns = Math.ceil(window.innerWidth / TILE_SIZE);
    const rows = Math.ceil(window.innerHeight / TILE_SIZE);
    const totalTiles = columns * rows;

    grid.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;

    for (let i = 0; i < totalTiles; i++) {
        const tile = document.createElement("div");
        tile.classList.add("tile");
        grid.appendChild(tile);
    }
}

createGrid();

let resizeTimeout;
window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(createGrid, 200);
});
