// RNF22: Leaflet se sirve desde el propio sitio. Solo se cargan desde OpenStreetMap los mosaicos del mapa.
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import marcador from 'leaflet/dist/images/marker-icon.png';
import marcador2x from 'leaflet/dist/images/marker-icon-2x.png';
import sombra from 'leaflet/dist/images/marker-shadow.png';

// Con el empaquetado, Leaflet no encuentra solo las imágenes del marcador por defecto.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl: marcador, iconRetinaUrl: marcador2x, shadowUrl: sombra });

window.L = L;
