import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import { Icon } from 'leaflet';
import 'leaflet/dist/leaflet.css';

interface ServiceArea {
    name: string;
    coordinates: [number, number];
}

const serviceAreas: ServiceArea[] = [
    { name: 'Abucay', coordinates: [14.7197, 120.5314] },
    { name: 'Bagac', coordinates: [14.5967, 120.3933] },
    { name: 'Balanga City', coordinates: [14.6761, 120.5361] },
    { name: 'Dinalupihan', coordinates: [14.8833, 120.4667] },
    { name: 'Hermosa', coordinates: [14.8314, 120.5083] },
    { name: 'Limay', coordinates: [14.5667, 120.6167] },
    { name: 'Mariveles', coordinates: [14.4333, 120.4833] },
    { name: 'Morong', coordinates: [14.6833, 120.2667] },
    { name: 'Orani', coordinates: [14.8000, 120.5333] },
    { name: 'Orion', coordinates: [14.6167, 120.5833] },
    { name: 'Pilar', coordinates: [14.6667, 120.5667] },
    { name: 'Samal', coordinates: [14.7667, 120.5500] }
];

// Create custom marker icon
const createCustomIcon = (color: string = '#3b82f6') => {
    return new Icon({
        iconUrl: `data:image/svg+xml;base64,${btoa(`
            <svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 12.5 12.5 28.5 12.5 28.5s12.5-16 12.5-28.5C25 5.596 19.404 0 12.5 0zm0 17c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" fill="${color}"/>
            </svg>
        `)}`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [0, -41]
    });
};

export default function InteractiveMap() {
    const bataanCenter: [number, number] = [14.68, 120.48];
    const customIcon = createCustomIcon('#059669'); // Emerald color

    return (
        <div className="contact-map-wrapper">
            <MapContainer
                center={bataanCenter}
                zoom={10}
                style={{ height: '400px', width: '100%' }}
                className="contact-map-container"
                scrollWheelZoom={true}
                zoomControl={true}
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                
                {serviceAreas.map((area, index) => (
                    <Marker
                        key={index}
                        position={area.coordinates}
                        icon={customIcon}
                    >
                        <Popup>
                            <div className="contact-map-popup">
                                <h4 className="font-semibold text-gray-900">{area.name}</h4>
                                <p className="text-sm text-gray-600">Service Area</p>
                            </div>
                        </Popup>
                    </Marker>
                ))}
            </MapContainer>
            
            <div className="contact-map-overlay">
                <span className="contact-map-overlay-text">Bataan Province Coverage</span>
            </div>
        </div>
    );
}
