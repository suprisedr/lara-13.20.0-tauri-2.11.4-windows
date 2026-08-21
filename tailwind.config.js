import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/* Theme mirrors the AFS design tokens in resources/css/app.css,
   which were extracted from CGL-YE25-Annual-Financial-Statements.docx. */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    '"Century Gothic"', '"URW Gothic"', '"Avant Garde"',
                    'Futura', '"Avenir Next"', 'Avenir', '"Trebuchet MS"',
                    ...defaultTheme.fontFamily.sans,
                ],
            },

            fontSize: {
                'afs-note':    ['7pt',    { lineHeight: '1.2857' }],
                'afs-folio':   ['8pt',    { lineHeight: '1.0714' }],
                'afs-header':  ['10pt',   { lineHeight: '1.0714' }],
                'afs-body':    ['10.5pt', { lineHeight: '1.2857' }],
                'afs-h3':      ['11.5pt', { lineHeight: '1.2857' }],
                'afs-h2':      ['13pt',   { lineHeight: '1.2857' }],
                'afs-title':   ['25pt',   { lineHeight: '1.1' }],
                'afs-display': ['27pt',   { lineHeight: '1.1' }],
            },

            colors: {
                afs: {
                    navy:      '#1A345B',
                    blue:      '#005BF0',
                    'blue-light': '#2674F2',
                    'blue-deep':  '#0047C4',
                    'blue-pale':  '#9EC1F5',
                    ink:       '#191919',
                    zebra:     '#EAF8FB',
                    'zebra-soft': '#F4FAFC',
                    canvas:    '#F7FBFD',
                    panel:     '#F2F2F2',
                    hairline:  '#D3E2F5',
                    'rule-grey': '#BFBFBF',
                    muted:     '#5A7186',
                    'muted-soft': '#6F869B',
                    negative:  '#B91C1C',
                },
            },
        },
    },

    plugins: [forms],
};
