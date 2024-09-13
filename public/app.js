function setupWheel(firstNames) {
    const wheel = document.getElementById('wheel-container');
    wheel.innerHTML = '';  // Réinitialise la roue
    const nameCount = firstNames.length;
    
    // Calcul de l'angle de chaque prénom
    const angleStep = 360 / nameCount;  // Angle en degrés pour chaque prénom
    
    // Créer les éléments li pour chaque prénom avec des tailles égales
    firstNames.forEach((name, index) => {
        const listItem = document.createElement('li');
        listItem.textContent = name;

        // Calculer l'angle de rotation pour chaque prénom
        const rotateAngle = angleStep * index;

        // Appliquer la transformation CSS
        listItem.style.transform = `rotate(${rotateAngle}deg) translateX(100%)`;
        
        wheel.appendChild(listItem);
    });
}
// Fonction pour faire tourner la roue
let currentRotation = 0;  // Stocke la rotation totale
let isSpinning = false;

function spinWheel() {

    if (isSpinning) {
        return;
    }

    // Désactiver le bouton et indiquer que la roue est en train de tourner
    isSpinning = true;
    document.getElementById('spin-button').disabled = true;

    const wheel = document.getElementById('wheel-container');
    const firstNames = Array.from(wheel.querySelectorAll('li'));
    const nameCount = firstNames.length;
    const spinSound = new Audio('effect.wav');  // Chemin vers le fichier MP3
    spinSound.play();  // Jouer le son lorsque la roue tourne

    // Vérifier le nombre de prénoms
    console.log("Nombre de prénoms dans la roue : ", nameCount);

    // Sélectionner un prénom au hasard
    const selectedIndex = Math.floor(Math.random() * nameCount);

    // Calculer l'angle du prénom sélectionné
    const angleStep = 360 / nameCount;
    const targetAngle = angleStep * selectedIndex;
    const currentOffset = currentRotation % 360;
    const totalRotation = (360 * 5) + (360 - targetAngle - currentOffset);  // 5 tours complets + angle nécessaire pour s'arrêter sur le prénom

    // Appliquer la rotation
    currentRotation += totalRotation;
    wheel.style.transition = 'transform 10s ease-out';
    wheel.style.transform = `rotate(${currentRotation}deg)`;

    // Après 10 secondes (durée de la rotation), afficher le gagnant
    setTimeout(function() {
        fetchAndDisplayName();  
        
        isSpinning = false;
        document.getElementById('spin-button').disabled = false;
    }, 7000);

    // Supprimer la transition après l'animation pour ne pas affecter les futures rotations
    setTimeout(function() {
        wheel.style.transition = 'none';
    }, 7000);
}

// Mettre à jour l'historique
function updateHistory(name) {
    let historyList = document.getElementById('history-list');
    let listItem = document.createElement('li');
    listItem.className = 'list-group-item';
    listItem.innerHTML = name;
}

// Fonction pour récupérer et afficher le prénom tiré
function fetchAndDisplayName() {
    fetch('/spin')
        .then(response => response.json())
        .then(data => {
            if (data.name) {
                document.getElementById('winner-name').textContent = data.name;

                // Ouvrir la modal Bootstrap
                const winnerModal = new bootstrap.Modal(document.getElementById('winnerModal'));
                winnerModal.show();
                console.log("Modal affichée avec le prénom : ", data.name);
                // Retirer le prénom de la roue
                removeNameFromWheel(data.name);
                
                // Mettre à jour l'historique
                updateHistory(data.name);

                // Ajouter le prénom tiré à la section "Réintégrer un prénom"
                addNameToRedrawList(data.name, data.id);

                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        });
}
// Fonction pour retirer le prénom de la roue
function removeNameFromWheel(name) {
    const wheelItems = Array.from(document.getElementById('wheel-container'));
    const updatedNames = wheelItems
        .filter(item => item.textContent !== name)
        .map(item => item.textContent);  // Garde les prénoms restants

    // Réinitialise la roue avec les prénoms restants
    setupWheel(updatedNames);
}


// Réintégrer un prénom dans la liste
function reAddName(id) {
    fetch('/reAddName/' + id, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

// Gestion du formulaire d'ajout de prénom
document.getElementById('add-name-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const name = document.getElementById('new-name').value;
    console.log(name);
    fetch('/add-name', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name })
    }).then(response => {
        if (response.ok) {
            location.reload();
        } else {
            alert('Erreur lors de l\'ajout du prénom');
        }
    });
});

// Initialisation de la roue
document.addEventListener('DOMContentLoaded', function() {
    const firstNames = JSON.parse(document.getElementById('firstNamesData').textContent);
    setupWheel(firstNames);
    
    // Lancer la roue au clic du bouton
    document.getElementById('spin-button').addEventListener('click', spinWheel);
});
// Fonction pour ajouter le prénom à la section "Réintégrer un prénom"
function addNameToRedrawList(name, id) {
    const redrawList = document.getElementById('redraw-list');
    const listItem = document.createElement('li');
    listItem.className = 'list-group-item d-flex justify-content-between align-items-center';

    // Contenu du prénom et du bouton
    listItem.innerHTML = `
        ${name}
        <button class="btn btn-outline-primary btn-sm" onclick="reAddName('${id}')">Réintégrer</button>
    `;

    // Ajouter l'élément dans la liste
    redrawList.appendChild(listItem);
}

document.getElementById('spin-button').addEventListener('click', function() {
    // Simuler un tirage de nom (peut aussi être remplacé par une requête AJAX)
    const name = document.getElementById('new-name').value;
    // Appeler la fonction pour mettre à jour l'historique
    updateHistory(name);
});

function reAddNameAll() {
    // Envoyer la requête POST pour réintégrer tous les prénoms
    fetch('/reAddNameAll', { method: 'POST' })
        .then(response => {
            // Vérifier si la requête est réussie (code 200-299)
            if (!response.ok) {
                throw new Error(`Erreur HTTP : ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log("Tous les prénoms ont été réintégrés !");
                location.reload();  // Recharger la page pour mettre à jour l'interface
            } else {
                console.log("Erreur lors de la réintégration des prénoms.");
            }
        })
        .catch(error => {
            console.error('Erreur lors de la requête:', error);
        });
}
