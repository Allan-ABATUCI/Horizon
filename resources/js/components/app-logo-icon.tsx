import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 32" xmlns="http://www.w3.org/2000/svg">
            {/* Dôme de la lanterne */}
            <path d="M8.5 8Q12 3 15.5 8Z" />
            {/* Chambre de la lanterne */}
            <rect x="9" y="8" width="6" height="4.5" />
            {/* Galerie (rebord) */}
            <rect x="8" y="12.5" width="8" height="1" />
            {/* Tour, bandes séparées par de fines bandes claires */}
            <rect x="9" y="13.5" width="6" height="4.5" />
            <rect x="9" y="19" width="6" height="5" />
            <rect x="9" y="25" width="6" height="4" />
            {/* Socle */}
            <rect x="7" y="29" width="10" height="2" />
        </svg>
    );
}
