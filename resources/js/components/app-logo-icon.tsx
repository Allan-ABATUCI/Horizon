import { HTMLAttributes } from 'react';

// Même caractères et mêmes indentations relatives que le fichier fourni —
// juste recadré sur le contenu visible (la marge vide autour ne servait
// qu'à gâcher la place disponible une fois réduit aux petites tailles
// utilisées dans l'app).
const LOGO_ASCII = `  (&&&&&&&&                  &&&&&&&&&
    &&&&&&                    &&&&&&&
    &&&&                      .&&&&&&
    &&&                       .&&&&&&
    &&                        .&&&&&&
    &     &                   .&&&&&&
      &                       .&&&&&&
                              .&&&&&&
           &&&&&&&&&&&&&&&&&&&&&&&&&&
    &&  *                     .&&&&&&
   &  &                       .&&&&&&
         &                    .&&&&&&
        &&                    .&&&&&&
       &&&                    .&&&&&&
      &&&&                    .&&&&&&
    &&&&&&                    .&&&&&&
   &&&&&&&                    &&&&&&&
&&&&&&&&&&&&&              &&&&&&&&&&&&&`;

export default function AppLogoIcon({ className }: HTMLAttributes<HTMLElement>) {
    return (
        <span className={className} style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden' }}>
            <pre
                style={{
                    margin: 0,
                    fontFamily: "Consolas, 'BitstreamVeraSansMono', 'Courier New', Courier, monospace",
                    fontSize: '4px',
                    lineHeight: 1.15,
                    letterSpacing: 0,
                    color: 'currentColor',
                    transform: 'scale(0.38)',
                }}
            >
                {LOGO_ASCII}
            </pre>
        </span>
    );
}
