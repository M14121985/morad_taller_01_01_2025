
//animaciones divs bienvenida menu

function startAnimation() {
    const animatedDiv = document.getElementById('animatedDiv');
    animatedDiv.style.transform = 'rotateY(10deg) rotateX(10deg) scale(1.1)';
}

function stopAnimation() {
    const animatedDiv = document.getElementById('animatedDiv');
    animatedDiv.style.transform = 'none';
}

function initMap() {
    var location = { lat: 35.292, lng: -2.938 };
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 15,
        center: location
    });
    var marker = new google.maps.Marker({
        position: location,
        map: map
    });
}