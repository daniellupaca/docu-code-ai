let umlCode = ""; // Inicializa la variable
function sendFile() {
    const fileInput = document.getElementById("fileInput");
    if (fileInput.files.length === 0) {
        alert("Por favor, selecciona un archivo.");
        return;
    }

    const formData = new FormData();
    formData.append("file", fileInput.files[0]);

    fetch("upload.php", { // Asegúrate de que este es el archivo PHP correcto
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            data.files.forEach(file => {
                let umlCode = file.uml; // Aquí se asume que OpenAI también devuelve UML
                generateUML(umlCode);
            });
        }
    })
    .catch(error => console.error("Error:", error));
}

function displayFiles(files) {
    const outputDiv = document.getElementById("fileOutput");
    outputDiv.innerHTML = "";

    files.forEach(file => {
        const fileContainer = document.createElement("div");
        fileContainer.classList.add("file-container");

        const fileName = document.createElement("h3");
        fileName.textContent = file.name;

        const fileComment = document.createElement("p");
        fileComment.textContent = file.comment;

        fileContainer.appendChild(fileName);
        fileContainer.appendChild(fileComment);

        if (file.plantuml_image) {
            const umlImage = document.createElement("img");
            umlImage.src = file.plantuml_image;
            umlImage.alt = "Diagrama UML generado";
            umlImage.classList.add("uml-image"); 

            fileContainer.appendChild(umlImage);
        }

        outputDiv.appendChild(fileContainer);
    });
}

function encodePlantUML(text) {
    // Corrige la codificación usando Base64 y Deflate
    return unescape(encodeURIComponent(text))
        .split("").map(c => c.charCodeAt(0))
        .map(n => String.fromCharCode((n & 0xFF00) >> 8, n & 0xFF))
        .join("");
}
console.log("Valor recibido para UML:", umlCode);
function generateUML(umlCode) {
    if (!umlCode) {
        console.error("Error: El código UML es undefined o vacío.");
        return;
    }
    
    console.log("Código UML recibido:", umlCode);  // Verifica el contenido

    const encoded = encodePlantUML(umlCode);
    const umlImageUrl = `https://www.plantuml.com/plantuml/png/~1${encoded}`;

    let img = document.createElement("img");
    img.src = umlImageUrl;
    img.alt = "Diagrama UML Generado";
    img.style.maxWidth = "100%";

    document.getElementById("umlOutput").innerHTML = "";
    document.getElementById("umlOutput").appendChild(img);
}
