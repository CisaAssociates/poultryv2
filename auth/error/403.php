<?php
require_once __DIR__ . '/../../config.php';

$title = '403';
$sub_title = 'Forbidden Access';

if (!isset($_SESSION['error-page'])) {
    header('Location: ' . view('auth.index'));
    exit;
}

unset($_SESSION['error-page']);

ob_start();
?>
<div class="card">

    <div class="card-body p-4">

        <div class="text-center">
            <style>
                svg#freepik_stories-403-error-forbidden:not(.animated) .animable {
                    opacity: 0;
                }

                svg#freepik_stories-403-error-forbidden.animated #freepik--Shadow--inject-62 {
                    animation: 1s 1 forwards cubic-bezier(.36, -0.01, .5, 1.38) slideUp;
                    animation-delay: 0s;
                }

                svg#freepik_stories-403-error-forbidden.animated #freepik--error-403-sign--inject-62 {
                    animation: 1s 1 forwards cubic-bezier(.36, -0.01, .5, 1.38) slideUp;
                    animation-delay: 0s;
                }

                svg#freepik_stories-403-error-forbidden.animated #freepik--Character--inject-62 {
                    animation: 1s 1 forwards cubic-bezier(.36, -0.01, .5, 1.38) slideRight, 1.5s Infinite linear wind;
                    animation-delay: 0s, 1s;
                }

                svg#freepik_stories-403-error-forbidden.animated #freepik--Barricade--inject-62 {
                    animation: 1s 1 forwards cubic-bezier(.36, -0.01, .5, 1.38) zoomIn;
                    animation-delay: 0s;
                }

                @keyframes slideUp {
                    0% {
                        opacity: 0;
                        transform: translateY(30px);
                    }

                    100% {
                        opacity: 1;
                        transform: inherit;
                    }
                }

                @keyframes slideRight {
                    0% {
                        opacity: 0;
                        transform: translateX(30px);
                    }

                    100% {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }

                @keyframes wind {
                    0% {
                        transform: rotate(0deg);
                    }

                    25% {
                        transform: rotate(1deg);
                    }

                    75% {
                        transform: rotate(-1deg);
                    }
                }

                @keyframes zoomIn {
                    0% {
                        opacity: 0;
                        transform: scale(0.5);
                    }

                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            </style>
            <svg class="animated" id="freepik_stories-403-error-forbidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs">
                <g id="freepik--Shadow--inject-62" class="animable" style="transform-origin: 250px 416.24px;">
                    <ellipse id="freepik--path--inject-62" cx="250" cy="416.24" rx="193.89" ry="11.32" style="fill: rgb(245, 245, 245); transform-origin: 250px 416.24px;" class="animable"></ellipse>
                </g>
                <g id="freepik--error-403-sign--inject-62" class="animable" style="transform-origin: 321.11px 232.938px;">
                    <path d="M326.47,407.57H314.94V60.12c0-1.87,11.53-1.87,11.53,0Z" style="fill: rgb(38, 50, 56); transform-origin: 320.705px 233.144px;" id="el74zoimo82kp" class="animable"></path>
                    <polygon points="326.47 190.6 314.94 192.92 314.94 176.97 326.47 176.97 326.47 190.6" id="el224ukm8cjvai" class="animable" style="transform-origin: 320.705px 184.945px;"></polygon>
                    <g id="elxa7qttd3l7">
                        <rect x="227.98" y="65.63" width="186.24" height="115.11" rx="12" style="fill: rgb(255, 255, 255); transform-origin: 321.1px 123.185px; transform: rotate(4.25deg);" class="animable" id="el0l9mgiqiujj"></rect>
                    </g>
                    <path d="M398.63,188.13c-.33,0-.67,0-1,0l-161.8-12a13.54,13.54,0,0,1-12.46-14.46l6.76-90.86a13.54,13.54,0,0,1,14.46-12.47l161.8,12a13.54,13.54,0,0,1,12.46,14.46l-6.76,90.86a13.53,13.53,0,0,1-13.45,12.5ZM243.57,61.24A10.53,10.53,0,0,0,233.11,71l-6.76,90.86A10.51,10.51,0,0,0,236,173.07l161.8,12a10.53,10.53,0,0,0,11.25-9.69l6.76-90.86a10.51,10.51,0,0,0-9.69-11.25l-161.8-12C244.1,61.25,243.83,61.24,243.57,61.24Z" style="fill: #407BFF; transform-origin: 321.11px 123.233px;" id="el9hhu3lah1ej" class="animable"></path>
                    <path d="M287.44,108.05l-20.32-1.52.68-9.17,22.12-22.65,9.73.72-1.84,24.68,5,.38-.64,8.65-5-.37-.56,7.51-9.72-.73Zm.65-8.66L289,86.75,277.35,98.59Z" style="fill: #407BFF; transform-origin: 284.965px 95.495px;" id="elsy7can6clz" class="animable"></path>
                    <path d="M306.64,96.67Q307.5,85.21,312,81t12.9-3.65a19,19,0,0,1,6.59,1.5,12.5,12.5,0,0,1,4.05,2.92,14.37,14.37,0,0,1,2.34,3.56A18,18,0,0,1,339,89.52a38.47,38.47,0,0,1,.43,9.45q-.83,10.94-4.89,15.72t-13.12,4.12a17.9,17.9,0,0,1-8.07-2.22,13.31,13.31,0,0,1-4.77-5.12,17.89,17.89,0,0,1-1.81-6.24A41.46,41.46,0,0,1,306.64,96.67Zm11.06.84q-.57,7.68.58,10.59c.76,1.93,2,3,3.72,3.1a4.14,4.14,0,0,0,3-1,7.38,7.38,0,0,0,2.11-3.63,39.42,39.42,0,0,0,1.19-8q.59-8-.56-10.85a4.35,4.35,0,0,0-3.86-3.06,4.15,4.15,0,0,0-4.21,2.51Q318.27,89.93,317.7,97.51Z" style="fill: #407BFF; transform-origin: 323.012px 98.0872px;" id="elw8gp60gbh6j" class="animable"></path>
                    <path d="M355.5,92l-10.41-2.67a13.25,13.25,0,0,1,5.63-7.35q3.94-2.42,10.79-1.9,7.86.59,11.15,3.78a9.24,9.24,0,0,1,3,7.63,8.21,8.21,0,0,1-1.78,4.6,12,12,0,0,1-4.57,3.38,12.94,12.94,0,0,1,3.46,1.61,8.72,8.72,0,0,1,2.87,3.48,9.8,9.8,0,0,1,.75,4.89A13.54,13.54,0,0,1,374,116a12.37,12.37,0,0,1-5.65,4.56,20.39,20.39,0,0,1-9.2,1.07,23.92,23.92,0,0,1-8.55-1.93,12.92,12.92,0,0,1-4.92-4.16,17.41,17.41,0,0,1-2.68-6.46l11.26-.65a8.67,8.67,0,0,0,1.7,4.82,5.25,5.25,0,0,0,7.28.2,6.26,6.26,0,0,0,1.82-4.28,6,6,0,0,0-1.12-4.43,5.09,5.09,0,0,0-3.79-1.82,14,14,0,0,0-3.66.38l1.17-7.93a10.93,10.93,0,0,0,1.44.25,5.1,5.1,0,0,0,3.76-1.13,4.38,4.38,0,0,0,1.71-3.2,4,4,0,0,0-.87-3A4.07,4.07,0,0,0,360.77,87a4.43,4.43,0,0,0-3.29,1A7.22,7.22,0,0,0,355.5,92Z" style="fill: #407BFF; transform-origin: 359.721px 100.874px;" id="elfs4kilngs7a" class="animable"></path>
                    <path d="M272.07,123.81,287.59,125l-.3,4-9.71-.72-.23,3,9,.68-.29,3.82-9-.67-.27,3.69,10,.75-.31,4.24-15.8-1.18Z" style="fill: #407BFF; transform-origin: 279.135px 133.8px;" id="el7pn2d2uh1bp" class="animable"></path>
                    <path d="M289.67,144l1.4-18.74,9.65.71a14.15,14.15,0,0,1,4.07.77,4.39,4.39,0,0,1,2.16,1.88,5.8,5.8,0,0,1-.22,5.74,5.24,5.24,0,0,1-2,1.7,6.93,6.93,0,0,1-2.09.59,5.8,5.8,0,0,1,1.46.8,5.53,5.53,0,0,1,.85,1.06,7.58,7.58,0,0,1,.73,1.22l2.4,5.64-6.55-.49-2.67-6a4.33,4.33,0,0,0-.94-1.52,2.41,2.41,0,0,0-1.38-.54l-.51,0-.57,7.61Zm6.65-10.72,2.44.18a8.05,8.05,0,0,0,1.55-.14,1.59,1.59,0,0,0,1-.51,1.8,1.8,0,0,0,.44-1.07,1.7,1.7,0,0,0-.47-1.43,3.52,3.52,0,0,0-2.12-.65l-2.55-.19Z" style="fill: #407BFF; transform-origin: 298.875px 135.315px;" id="elhgm5pljflia" class="animable"></path>
                    <path d="M310,145.48l1.39-18.74,9.66.72a14.13,14.13,0,0,1,4.06.76,4.42,4.42,0,0,1,2.17,1.88,5.32,5.32,0,0,1-2.19,7.44,7,7,0,0,1-2.09.59,6,6,0,0,1,1.46.8,6.83,6.83,0,0,1,.85,1.06,6.69,6.69,0,0,1,.72,1.22l2.4,5.64-6.54-.48-2.67-6a4.37,4.37,0,0,0-.94-1.52,2.39,2.39,0,0,0-1.39-.54l-.51,0-.56,7.61Zm6.65-10.71,2.44.18a9.07,9.07,0,0,0,1.55-.14,1.51,1.51,0,0,0,1-.52,1.72,1.72,0,0,0,.45-1.06,1.75,1.75,0,0,0-.47-1.44,3.47,3.47,0,0,0-2.13-.64L317,131Z" style="fill: #407BFF; transform-origin: 319.215px 136.795px;" id="elwfrwayimbp" class="animable"></path>
                    <path d="M329.85,137.55a8.9,8.9,0,0,1,10.4-9q4.68.34,7,3.05a9.54,9.54,0,0,1,2,7.23,11.5,11.5,0,0,1-1.51,5.3,7.92,7.92,0,0,1-3.44,3A10.56,10.56,0,0,1,339,148a12,12,0,0,1-5.18-1.4,7.92,7.92,0,0,1-3.13-3.45A10.66,10.66,0,0,1,329.85,137.55Zm5.79.45a6.33,6.33,0,0,0,.75,4.16,4,4,0,0,0,5.76.45c.74-.76,1.19-2.18,1.34-4.28a5.84,5.84,0,0,0-.78-4,3.66,3.66,0,0,0-2.8-1.43,3.51,3.51,0,0,0-2.91,1A6.28,6.28,0,0,0,335.64,138Z" style="fill: #407BFF; transform-origin: 339.555px 138.235px;" id="el1mk8bca27a" class="animable"></path>
                    <path d="M351.77,148.59l1.39-18.75,9.65.72a14.15,14.15,0,0,1,4.07.77A4.34,4.34,0,0,1,369,133.2a5.18,5.18,0,0,1,.65,3.1,5.28,5.28,0,0,1-.87,2.65,5.19,5.19,0,0,1-2,1.69,7.08,7.08,0,0,1-2.09.6,4,4,0,0,1,2.31,1.85,7.34,7.34,0,0,1,.72,1.23l2.4,5.64-6.54-.49-2.67-6a4.48,4.48,0,0,0-.94-1.52,2.46,2.46,0,0,0-1.39-.54l-.51,0-.57,7.61Zm6.64-10.72,2.44.18a8.13,8.13,0,0,0,1.56-.14,1.51,1.51,0,0,0,1-.52,1.7,1.7,0,0,0,.45-1.06,1.76,1.76,0,0,0-.47-1.43,3.46,3.46,0,0,0-2.13-.65l-2.54-.19Z" style="fill: #407BFF; transform-origin: 360.945px 139.9px;" id="elp1s88k5rm2b" class="animable"></path>
                    <path d="M252.75,163.34l1.27-17.1,11.54.86-.15,2-9.28-.69-.39,5.29,8,.6-.15,2-8-.6-.58,7.77Z" style="fill: rgb(38, 50, 56); transform-origin: 259.155px 154.855px;" id="elrvxl6dvsw1n" class="animable"></path>
                    <path d="M267.16,156a9.35,9.35,0,0,1,2.78-6.5,7.79,7.79,0,0,1,6.08-2,8.23,8.23,0,0,1,4.19,1.45,7.43,7.43,0,0,1,2.66,3.37,10.33,10.33,0,0,1,.66,4.67,10.11,10.11,0,0,1-1.4,4.57,7.16,7.16,0,0,1-3.21,2.89,8.49,8.49,0,0,1-4.23.75,8.13,8.13,0,0,1-4.23-1.49,7.46,7.46,0,0,1-2.64-3.4A9.73,9.73,0,0,1,267.16,156Zm2.33.21a6.88,6.88,0,0,0,1.3,5,5.81,5.81,0,0,0,8.37.61,7.29,7.29,0,0,0,2-5,8.76,8.76,0,0,0-.43-3.7,5.43,5.43,0,0,0-1.88-2.56,5.69,5.69,0,0,0-3-1.09,5.79,5.79,0,0,0-4.26,1.35C270.39,151.88,269.68,153.68,269.49,156.25Z" style="fill: rgb(38, 50, 56); transform-origin: 275.353px 156.342px;" id="elt56oyyoutzm" class="animable"></path>
                    <path d="M285.84,165.8l1.28-17.1,7.58.57a10,10,0,0,1,3.44.72,3.79,3.79,0,0,1,1.78,1.76,4.88,4.88,0,0,1,.52,2.64,4.32,4.32,0,0,1-1.41,3,6,6,0,0,1-3.75,1.31,6.08,6.08,0,0,1,1.3.95,12.59,12.59,0,0,1,1.71,2.42l2.63,4.88-2.85-.22-2-3.72c-.58-1.08-1.07-1.9-1.46-2.48a4.92,4.92,0,0,0-1.06-1.23,3.46,3.46,0,0,0-1-.53,7.67,7.67,0,0,0-1.25-.18l-2.63-.19-.56,7.59Zm3-9.38,4.86.36a7.24,7.24,0,0,0,2.45-.14,2.59,2.59,0,0,0,1.41-.93,2.78,2.78,0,0,0,.57-1.5,2.59,2.59,0,0,0-.73-2.06,4.15,4.15,0,0,0-2.73-1l-5.41-.41Z" style="fill: rgb(38, 50, 56); transform-origin: 293.38px 157.825px;" id="eli3lsz7q6l7" class="animable"></path>
                    <path d="M303,167.08l1.27-17.1,6.42.47a7.9,7.9,0,0,1,3.1.76,4.09,4.09,0,0,1,1.74,1.73,4.26,4.26,0,0,1,.5,2.31,3.79,3.79,0,0,1-.75,2,4.22,4.22,0,0,1-1.91,1.43,4.49,4.49,0,0,1,2.27,1.73,4.2,4.2,0,0,1,.64,2.65,5.05,5.05,0,0,1-.67,2.2,4.38,4.38,0,0,1-1.38,1.5,5.24,5.24,0,0,1-1.93.7,11.22,11.22,0,0,1-2.78.08Zm2.41-1.85,4.26.32a10.71,10.71,0,0,0,1.55,0,3.92,3.92,0,0,0,1.34-.37,2.54,2.54,0,0,0,.93-.89,3.11,3.11,0,0,0,.08-3.11,2.59,2.59,0,0,0-1.29-1.1,8.49,8.49,0,0,0-2.48-.47l-3.95-.29Zm.59-7.9,3.7.28a8.44,8.44,0,0,0,2.17,0,2.37,2.37,0,0,0,1.36-.76,2.42,2.42,0,0,0,.55-1.46,2.81,2.81,0,0,0-.29-1.53,2.12,2.12,0,0,0-1.11-1,9.54,9.54,0,0,0-2.58-.43l-3.42-.26Z" style="fill: rgb(38, 50, 56); transform-origin: 309.649px 158.786px;" id="elhbz1ir1k07w" class="animable"></path>
                    <path d="M319.38,168.3l1.27-17.1,2.27.17-1.28,17.1Z" style="fill: rgb(38, 50, 56); transform-origin: 321.15px 159.835px;" id="elrq9sg95idx" class="animable"></path>
                    <path d="M325.63,168.76l1.28-17.1,5.89.44a14.61,14.61,0,0,1,3,.47,5.83,5.83,0,0,1,2.42,1.41,7,7,0,0,1,1.81,3.08,11.32,11.32,0,0,1,.36,4.11,11.68,11.68,0,0,1-.71,3.42,8.25,8.25,0,0,1-1.35,2.4,6.15,6.15,0,0,1-1.67,1.44,6.39,6.39,0,0,1-2.11.71,11.82,11.82,0,0,1-2.77.08Zm2.42-1.84,3.65.27a9.1,9.1,0,0,0,2.68-.12,3.86,3.86,0,0,0,1.6-.77,5.53,5.53,0,0,0,1.41-2.08,10.59,10.59,0,0,0,.7-3.26,8,8,0,0,0-.58-4.19,4.47,4.47,0,0,0-2-2.09,9.1,9.1,0,0,0-2.9-.56l-3.59-.27Z" style="fill: rgb(38, 50, 56); transform-origin: 333.036px 160.463px;" id="el9sp4y1sdnik" class="animable"></path>
                    <path d="M342.89,170.05l1.27-17.1,5.89.43a14.07,14.07,0,0,1,3,.48,5.75,5.75,0,0,1,2.41,1.41,6.94,6.94,0,0,1,1.81,3.07,11.54,11.54,0,0,1,.37,4.11,12.37,12.37,0,0,1-.71,3.42,8.19,8.19,0,0,1-1.36,2.41,6.1,6.1,0,0,1-1.67,1.43,6.34,6.34,0,0,1-2.1.71,12.39,12.39,0,0,1-2.77.09Zm2.41-1.85,3.65.27a9.1,9.1,0,0,0,2.68-.12,3.8,3.8,0,0,0,1.6-.77,5.45,5.45,0,0,0,1.41-2.07,10.59,10.59,0,0,0,.7-3.26,8,8,0,0,0-.58-4.19,4.41,4.41,0,0,0-2-2.09,8.85,8.85,0,0,0-2.91-.57l-3.59-.27Z" style="fill: rgb(38, 50, 56); transform-origin: 350.288px 161.75px;" id="elwbepgsj87t" class="animable"></path>
                    <path d="M360.19,171.33l1.27-17.1,12.36.92-.15,2-10.1-.75-.39,5.24,9.46.7-.15,2-9.46-.71-.43,5.82,10.5.79-.15,2Z" style="fill: rgb(38, 50, 56); transform-origin: 367.005px 163.235px;" id="el0qqx86dx0s4a" class="animable"></path>
                    <path d="M376.05,172.51l1.27-17.1,2.32.17,8,14.1,1-13.43,2.17.16-1.28,17.11-2.32-.18-8-14.1-1,13.43Z" style="fill: rgb(38, 50, 56); transform-origin: 383.43px 164.465px;" id="eloc4qxe0gk7e" class="animable"></path>
                </g>
                <g id="freepik--Character--inject-62" class="animable" style="transform-origin: 163.604px 259.038px;">
                    <path d="M169.92,164.89l.33,2,.38,2.09c.28,1.39.54,2.79.86,4.17.61,2.78,1.31,5.52,2.11,8.17.4,1.32.84,2.63,1.31,3.89s1,2.52,1.49,3.7a30.56,30.56,0,0,0,3.67,6.23l-2.74-1.8a3.93,3.93,0,0,0,2.29.53,10.17,10.17,0,0,0,3.74-1.1,27.39,27.39,0,0,0,4.29-2.66c.72-.53,1.45-1.09,2.15-1.69.36-.29.71-.59,1-.89s.73-.64,1-.85l.53-.43a3.72,3.72,0,0,1,5.7,4.47c-.3.63-.5,1.11-.77,1.63s-.53,1-.81,1.5c-.58,1-1.19,1.95-1.87,2.89a29.71,29.71,0,0,1-4.86,5.31,22.39,22.39,0,0,1-3.23,2.28,17.69,17.69,0,0,1-8.72,2.51,17.06,17.06,0,0,1-5-.72,5.34,5.34,0,0,1-2.35-1.4l-.38-.39a39.4,39.4,0,0,1-6.5-9A64.13,64.13,0,0,1,159.5,186a86.24,86.24,0,0,1-2.77-9.5c-.36-1.59-.71-3.19-1-4.8-.14-.81-.28-1.61-.4-2.43s-.22-1.59-.32-2.58a7.5,7.5,0,0,1,14.84-2.08Z" style="fill: rgb(38, 50, 56); transform-origin: 176.676px 182.661px;" id="elfsf08mwoags" class="animable"></path>
                    <path d="M191.67,186.08c-2.11.67-4.59,3.11-3.81,6s9.33,5,9.61-.14C195,187,191.67,186.08,191.67,186.08Z" style="fill: rgb(255, 255, 255); transform-origin: 192.592px 190.573px;" id="elabov85v874" class="animable"></path>
                    <path d="M190.54,188.87l.91-1.39L200.8,185s1.36,5.95-5.39,8.21A1.88,1.88,0,0,1,194,193l-2.69-1.4A1.94,1.94,0,0,1,190.54,188.87Z" style="fill: rgb(181, 91, 82); transform-origin: 195.586px 189.132px;" id="el6ck1i4j2llp" class="animable"></path>
                    <path d="M120.08,154.75c11.94-2.17,28.12-2.58,39.85-.57a11.63,11.63,0,0,1,9.64,12.46c-.67,7.71-1.52,14.52-2,19.86.53,13.56-.62,36.22-.62,41l-49.69.7c-2.21-6.19-1.48-34.08-5.36-62.15A10,10,0,0,1,120.08,154.75Z" style="fill: rgb(38, 50, 56); transform-origin: 140.704px 190.538px;" id="el2ubzvd5mgbx" class="animable"></path>
                    <g id="el0g5mgm0brxmb">
                        <path d="M145.59,227.52l-1-.13c2.82-22.25,0-59.53,0-59.9l1-.08C145.62,167.79,148.43,205.17,145.59,227.52Z" style="opacity: 0.2; transform-origin: 145.724px 197.465px;" class="animable" id="eljm9mcowsra"></path>
                    </g>
                    <path d="M150.1,155.32c-2-.53-.19-2.55-.19-2.55a96.32,96.32,0,0,1,15,1.21c.94.72-.08,1.65-.08,1.65S155.92,156.9,150.1,155.32Z" style="fill: #407BFF; transform-origin: 157.234px 154.441px;" id="eladt8op545xf" class="animable"></path>
                    <g id="el5u9oflzowow">
                        <path d="M147.29,152.47s3,5.92-1,11.3a12.32,12.32,0,0,1,6,2s1.41-5.07-2.56-12.79C149.3,152.25,147.29,152.47,147.29,152.47Z" style="opacity: 0.2; transform-origin: 149.412px 159.104px;" class="animable" id="eljscxvmgewrn"></path>
                    </g>
                    <path d="M150.44,155.34c.77,3.61-5.94,10.43-5.94,10.43s-11.86-6.22-12.61-9.79Z" style="fill: #407BFF; transform-origin: 141.196px 160.555px;" id="el9cis4ohy8xi" class="animable"></path>
                    <g id="el417kjugjvwm">
                        <path d="M119.36,172.37c1.3,2.51-.28,13-5,18.23-.31-4.58-.7-9.37-1.2-14.24Z" style="opacity: 0.2; transform-origin: 116.479px 181.485px;" class="animable" id="el3j7ldd8zkv4"></path>
                    </g>
                    <path d="M133.16,136.82l13.44,6.44c-1.42,3.81-2.1,7.77,1.88,9.66a4.48,4.48,0,0,1,1.94,6.26,7.8,7.8,0,0,1-6.11,3.89c-4.67.47-9.27-2.53-11.95-5.34-1.1-1.16,0-2.52.12-4.14C133,147.87,133.88,141.88,133.16,136.82Z" style="fill: rgb(181, 91, 82); transform-origin: 141.444px 149.969px;" id="el3j796unmvrv" class="animable"></path>
                    <g id="el727wn63ejt9">
                        <path d="M139.35,138.1l7.25,5.16a18.22,18.22,0,0,0-.95,3.27c-3,1-7.7-1.62-7.78-4.22C137.83,141,139,138.72,139.35,138.1Z" style="opacity: 0.2; transform-origin: 142.235px 142.424px;" class="animable" id="el9kv5go895ft"></path>
                    </g>
                    <path d="M148.77,115.09c2.57,2.09,5.5,11.43,1.73,15.2C144.94,123.83,144.31,114.12,148.77,115.09Z" style="fill: rgb(38, 50, 56); transform-origin: 149.062px 122.656px;" id="el21ovqulu5a2" class="animable"></path>
                    <path d="M129.44,125.9c1,8,1.2,12.7,5.52,16.51,6.49,5.73,16.14,1.32,17.53-6.81,1.25-7.33-.62-19.13-8.67-21.74A11.07,11.07,0,0,0,129.44,125.9Z" style="fill: rgb(181, 91, 82); transform-origin: 141.09px 129.164px;" id="el3sb12mfcbnv" class="animable"></path>
                    <path d="M129.69,132.78c3.78.28,5.09-8.86,2.74-14,1.64.68,6.31,0,7.61-2.13,7.54,2.14,10.36,2.18,11.32-.93s-10.89-5.85-17.75-4.38-4.84,6.44-4.84,6.44S117.15,121.5,129.69,132.78Z" style="fill: rgb(38, 50, 56); transform-origin: 137.641px 121.859px;" id="el6bcfreycy2g" class="animable"></path>
                    <path d="M126.93,133.39a7.54,7.54,0,0,0,4.44,3.3c2.33.56,3.13-1.78,2.19-4.13-.86-2.11-3.09-5-5.29-4.45S125.62,131.32,126.93,133.39Z" style="fill: rgb(181, 91, 82); transform-origin: 130.072px 132.408px;" id="el69vlzr6aeyh" class="animable"></path>
                    <path d="M140.1,127.24c0,.65.42,1.15.85,1.12s.74-.58.69-1.24-.42-1.15-.85-1.12S140.06,126.59,140.1,127.24Z" style="fill: rgb(38, 50, 56); transform-origin: 140.871px 127.18px;" id="elzmgpett9hy" class="animable"></path>
                    <path d="M148.94,126.52c.05.65.43,1.16.85,1.12s.74-.58.7-1.23-.42-1.16-.85-1.13S148.9,125.87,148.94,126.52Z" style="fill: rgb(38, 50, 56); transform-origin: 149.715px 126.46px;" id="elzewz0hpmko" class="animable"></path>
                    <path d="M147.51,127.19a22.91,22.91,0,0,0,3.49,5.34,3.77,3.77,0,0,1-3.08.82Z" style="fill: rgb(160, 39, 36); transform-origin: 149.255px 130.299px;" id="elcgv7smtzkwr" class="animable"></path>
                    <path d="M143.46,122.08a.31.31,0,0,1,.09.17.39.39,0,0,1-.28.46,3.85,3.85,0,0,1-3.44-.76.38.38,0,0,1,0-.54.4.4,0,0,1,.55,0h0a3.07,3.07,0,0,0,2.73.58A.36.36,0,0,1,143.46,122.08Z" style="fill: rgb(38, 50, 56); transform-origin: 141.638px 122.069px;" id="elx0q4ngsdv9g" class="animable"></path>
                    <path d="M145.08,124a.32.32,0,0,1-.14-.14.38.38,0,0,1,.13-.52,3.88,3.88,0,0,1,3.51-.31.38.38,0,0,1,.18.51.4.4,0,0,1-.52.18h0a3.08,3.08,0,0,0-2.77.27A.37.37,0,0,1,145.08,124Z" style="fill: rgb(38, 50, 56); transform-origin: 146.842px 123.4px;" id="ely76nibgclkg" class="animable"></path>
                    <path d="M152.57,120.34a.4.4,0,0,1-.35-.07,3,3,0,0,0-2.69-.67.38.38,0,0,1-.22-.73,3.73,3.73,0,0,1,3.4.8.39.39,0,0,1,0,.54A.41.41,0,0,1,152.57,120.34Z" style="fill: rgb(38, 50, 56); transform-origin: 150.929px 119.551px;" id="elt96d7sc4avi" class="animable"></path>
                    <path d="M196.16,220h0a1.67,1.67,0,0,0-2.26-.67l-12.23,6.61-5.78-10.7a1.67,1.67,0,0,0-2.94,1.58l5.78,10.7-42.15,22.78a1.68,1.68,0,0,0-.68,2.26h0a1.68,1.68,0,0,0,2.26.68l57.33-31A1.67,1.67,0,0,0,196.16,220Z" style="fill: rgb(38, 50, 56); transform-origin: 166.029px 233.925px;" id="el2xvhsxu48n5" class="animable"></path>
                    <path d="M195.26,276.18c-20-15-81.57-21-60.29-48.42l32-.3s45,23.1,47.45,47.26c2.51,25.13,7,64.7,7,64.7l-12.4,4.4S190.9,295.3,195.26,276.18Z" style="fill: #407BFF; transform-origin: 175.99px 285.64px;" id="el39a7lokryz1" class="animable"></path>
                    <g id="elhn3iu82tb7l">
                        <path d="M136.07,241.5c-.74,1.62-1.42,3.3-2,5.07,5,5.65,14.81,10,25.44,14C156.21,254.81,149,248.41,136.07,241.5Z" style="opacity: 0.2; transform-origin: 146.79px 251.035px;" class="animable" id="elpfbzr1kjl5"></path>
                    </g>
                    <path d="M209.13,348.21,222,344.9a3.75,3.75,0,0,1,2.45.12c4.48,2,7.34,1.42,11.77,1.15,5.3-.34,4.11,5.36.38,6.33-6.15,1.6-5.7,1.25-11,2.62l-3.64.94-2.09-1.27-1,2-6.75,1.64a1.29,1.29,0,0,1-1.61-1Z" style="fill: rgb(38, 50, 56); transform-origin: 224.467px 351.613px;" id="el1urm9y2cnkjj" class="animable"></path>
                    <path d="M209.46,349.47c.19-.09,14.62-4.13,14.62-4.13l-2.24-11.09-16,4.35Z" style="fill: rgb(38, 50, 56); transform-origin: 214.96px 341.86px;" id="el20ecxc1esmz" class="animable"></path>
                    <path d="M119.77,309.82c.61-36.43-7-73.52-2.48-81.66l37.12-.52s-11.09,60.74-14,85c-3,25.24-18,90.86-18,90.86l-12,.19S111.83,336,119.77,309.82Z" style="fill: #407BFF; transform-origin: 132.41px 315.665px;" id="elxwzpqxkc7da" class="animable"></path>
                    <path d="M110,405.63l11-.17a3.82,3.82,0,0,1,2.38.7c4,3,4.42,3.12,8.8,3.9,5.26.93,2.9,6.18-.95,6.24-6.36.1-5.84-.13-11.26-.05l-5.89.05-.86-1.73-1,1.73h-1.55a1.32,1.32,0,0,1-1.32-1.4Z" style="fill: rgb(38, 50, 56); transform-origin: 122.289px 410.891px;" id="eltzven373jf" class="animable"></path>
                    <path d="M109.92,406.94c.19,0,13.85.63,13.85.63l.91-12.48-14.84.42Z" style="fill: rgb(38, 50, 56); transform-origin: 117.26px 401.33px;" id="elgh17hswah3" class="animable"></path>
                    <path d="M146.8,151.76s3.44,6.63-.51,12a21.47,21.47,0,0,1,6.55,1.36s2.79-6.75-3.36-12.79C149.05,151.61,146.8,151.76,146.8,151.76Z" style="fill: rgb(255, 255, 255); transform-origin: 149.881px 158.434px;" id="elpvy0gw8ge5" class="animable"></path>
                    <path d="M115.93,232c17.75,1.06,38.1,1.38,50.83,0a.45.45,0,0,0,.35-.27,16.05,16.05,0,0,0,1.1-6.79c0-.36-.24-.63-.48-.62-16.8.65-33.5.61-51,0-.19,0-.38.16-.45.43a21.33,21.33,0,0,0-.75,6.63A.6.6,0,0,0,115.93,232Z" style="fill: rgb(38, 50, 56); transform-origin: 141.872px 228.619px;" id="elka6bshkfk6t" class="animable"></path>
                    <g id="elxhz05psev4b">
                        <path d="M115.93,232c17.75,1.06,38.1,1.38,50.83,0a.45.45,0,0,0,.35-.27,16.05,16.05,0,0,0,1.1-6.79c0-.36-.24-.63-.48-.62-16.8.65-33.5.61-51,0-.19,0-.38.16-.45.43a21.33,21.33,0,0,0-.75,6.63A.6.6,0,0,0,115.93,232Z" style="opacity: 0.2; transform-origin: 141.872px 228.619px;" class="animable" id="el3330jx2ddr1"></path>
                    </g>
                    <path d="M146.21,234.73a30.69,30.69,0,0,1-3.76-.24l-.85-.11,0-.85c0-.22-.13-5.39.15-9.54l.06-.84.83-.08a48.86,48.86,0,0,1,7.59-.14l.9.06,0,.9a60.62,60.62,0,0,1-.34,9.56l-.09.59-.56.2A13,13,0,0,1,146.21,234.73Zm-2.65-2.13a20.89,20.89,0,0,0,5.35-.09,59.12,59.12,0,0,0,.26-7.63,46.76,46.76,0,0,0-5.49.1C143.52,227.85,143.54,231.08,143.56,232.6Z" style="fill: rgb(255, 255, 255); transform-origin: 146.379px 228.79px;" id="eldsex1m69gbc" class="animable"></path>
                    <path d="M126.92,224.26s-.29,5.61-.7,9.18c-.79.82-2.26.19-2.26.19s.29-5.18.64-9.25A4.88,4.88,0,0,1,126.92,224.26Z" style="fill: #407BFF; transform-origin: 125.44px 229.017px;" id="elzdv954y36b" class="animable"></path>
                    <path d="M163.56,224.26s-.29,5.61-.7,9.18c-.79.82-2.26.19-2.26.19s.29-5.18.64-9.25A4.88,4.88,0,0,1,163.56,224.26Z" style="fill: #407BFF; transform-origin: 162.08px 229.017px;" id="elivehq5u1dme" class="animable"></path>
                    <path d="M152,173.63c.54,7.23,5.15,8.86,5.8,8.84s5-1.82,4.46-9.05a8.86,8.86,0,0,1-5.28-1.95A7,7,0,0,1,152,173.63Z" style="fill: #407BFF; transform-origin: 157.153px 176.97px;" id="elo8pyufursa" class="animable"></path>
                    <path d="M151,114.43c5.14.86,7.08,3.25-4.49,4.65a91.13,91.13,0,0,1-20.16.34S141.78,114.38,151,114.43Z" style="fill: rgb(38, 50, 56); transform-origin: 140.704px 117.126px;" id="elohhhe960lhm" class="animable"></path>
                    <path d="M125.41,120a139.1,139.1,0,0,1,25.94-5.26,7.55,7.55,0,0,0-2.23-5.54s5.85-1,2.94-2.75c-2-1.17-6.77-1.3-12.06-4.7-2.17,2-8.11,5.2-12.17,5.61-1,1.72-3.63,6-7.88,7.68.06,1.88,4.29,2.37,4.29,2.37A3.2,3.2,0,0,0,125.41,120Z" style="fill: #407BFF; transform-origin: 136.411px 110.875px;" id="el00h6art80l1lc" class="animable"></path>
                    <g id="elczon7ti58jv">
                        <path d="M124.24,117.44c8.61-5.59,16.87-8,24.88-8.21a7.55,7.55,0,0,1,2.23,5.54A139.1,139.1,0,0,0,125.41,120,3.2,3.2,0,0,1,124.24,117.44Z" style="fill: rgb(38, 50, 56); opacity: 0.8; transform-origin: 137.796px 114.615px;" class="animable" id="elb6q4gms9opo"></path>
                    </g>
                    <path d="M139.51,107.7c1,4.92,4.47,5.82,4.94,5.78s3.47-1.48,2.48-6.4a7,7,0,0,1-4-1.09A4.89,4.89,0,0,1,139.51,107.7Z" style="fill: rgb(255, 255, 255); transform-origin: 143.32px 109.736px;" id="eltf4igpccc5l" class="animable"></path>
                    <path d="M145.35,123.07c1.14.16,3,8.74-2.95,9.2-5,.39-6.57-4.2-6.57-5.81C135.83,124.06,143.73,122.84,145.35,123.07Z" style="fill: rgb(38, 50, 56); transform-origin: 141.19px 127.668px;" id="eltb1s2gtezc" class="animable"></path>
                    <path d="M147.87,123c-.71.43.89,9.57,5,8.61,3.5-.81,2.91-5.78,2.34-7.39C154.4,121.79,148.88,122.36,147.87,123Z" style="fill: rgb(38, 50, 56); transform-origin: 151.675px 127.063px;" id="el1rpn52j816i" class="animable"></path>
                    <g id="elyr1ix2ckgr8">
                        <g style="opacity: 0.2; transform-origin: 146.59px 127.305px;" class="animable" id="el1ocma2n2jke">
                            <path d="M143.24,123.16l3.11,5.67a4.37,4.37,0,0,1-1.71,2.7l-4.29-7.84A23.51,23.51,0,0,1,143.24,123.16Z" style="fill: rgb(255, 255, 255); transform-origin: 143.35px 127.345px;" id="el2gwuqyout6m" class="animable"></path>
                            <path d="M139.08,124l4.41,8a5.06,5.06,0,0,1-1.08.19,5.93,5.93,0,0,1-.72,0l-4.18-7.62A10.92,10.92,0,0,1,139.08,124Z" style="fill: rgb(255, 255, 255); transform-origin: 140.5px 128.1px;" id="el2q4g800cfqr" class="animable"></path>
                            <path d="M152.91,122.61l2.76,5a5.48,5.48,0,0,1-.69,2.45l-4.18-7.65A9.81,9.81,0,0,1,152.91,122.61Z" style="fill: rgb(255, 255, 255); transform-origin: 153.235px 126.235px;" id="el08o4ocaekpco" class="animable"></path>
                            <path d="M154.44,130.77a2.77,2.77,0,0,1-.88.59l-4.77-8.7a9.55,9.55,0,0,1,1.11-.18Z" style="fill: rgb(255, 255, 255); transform-origin: 151.615px 126.92px;" id="elpyau86vzkc" class="animable"></path>
                        </g>
                    </g>
                    <path d="M209.05,177.94c.51-.22-.35-2.26-.86-2.05l-69.94,29.34a.65.65,0,0,0-.81-.26l-2.93,1.22a.67.67,0,0,0-.38.77l-9.2,3.85a15.22,15.22,0,0,0-5.38,3.84l-4.12,4.6a4.16,4.16,0,0,1-4.24,3.86l-.61,0L89.44,235.88a4.22,4.22,0,0,0-1.9,4.67,28.3,28.3,0,0,0,3.68,8.65,4.21,4.21,0,0,0,6.42.69L119,229.32a5.27,5.27,0,0,0,1.59-3.28l.26-2.64,4.62-5.16a5.25,5.25,0,0,0,9.38-4.69,1,1,0,0,0-.09-.18l1.71-.72a.67.67,0,0,0,.83.28l2.93-1.22a.67.67,0,0,0,.38-.79l40.79-17.11a.93.93,0,0,0,.49-1.22h0l22-9.23c.34-.14-.19-1.39-.53-1.24l-1.62.68-.09-1.78Zm-77.41,41.48a4.15,4.15,0,0,1-5.35-2l.61-.69.31-.13a5,5,0,0,0,.93,1,5.24,5.24,0,0,0,3.89,1.09l-.14-1.1a4.15,4.15,0,0,1-3.07-.86,4.07,4.07,0,0,1-.56-.54l5.52-2.31a1.4,1.4,0,0,0,.09.17A4.16,4.16,0,0,1,131.64,219.42Zm68.14-35.78-18.37,7.7-.65-1.54,18.93-7.94Z" style="fill: #407BFF; transform-origin: 148.305px 213.472px;" id="elf9ceyk4kxh" class="animable"></path>
                    <g id="eldj32q6q4049">
                        <path d="M209.05,177.94c.51-.22-.35-2.26-.86-2.05l-69.94,29.34a.65.65,0,0,0-.81-.26l-2.93,1.22a.67.67,0,0,0-.38.77l-9.2,3.85a15.22,15.22,0,0,0-5.38,3.84l-4.12,4.6a4.16,4.16,0,0,1-4.24,3.86l-.61,0L89.44,235.88a4.22,4.22,0,0,0-1.9,4.67,28.3,28.3,0,0,0,3.68,8.65,4.21,4.21,0,0,0,6.42.69L119,229.32a5.27,5.27,0,0,0,1.59-3.28l.26-2.64,4.62-5.16a5.25,5.25,0,0,0,9.38-4.69,1,1,0,0,0-.09-.18l1.71-.72a.67.67,0,0,0,.83.28l2.93-1.22a.67.67,0,0,0,.38-.79l40.79-17.11a.93.93,0,0,0,.49-1.22h0l22-9.23c.34-.14-.19-1.39-.53-1.24l-1.62.68-.09-1.78Zm-77.41,41.48a4.15,4.15,0,0,1-5.35-2l.61-.69.31-.13a5,5,0,0,0,.93,1,5.24,5.24,0,0,0,3.89,1.09l-.14-1.1a4.15,4.15,0,0,1-3.07-.86,4.07,4.07,0,0,1-.56-.54l5.52-2.31a1.4,1.4,0,0,0,.09.17A4.16,4.16,0,0,1,131.64,219.42Zm68.14-35.78-18.37,7.7-.65-1.54,18.93-7.94Z" style="opacity: 0.2; transform-origin: 148.305px 213.472px;" class="animable" id="elod7xn1kfx5"></path>
                    </g>
                    <path d="M182.07,189.63l-19.38,8.13a1.37,1.37,0,0,0-.76,1.72l.8,2.2a1.36,1.36,0,0,0,1.81.8l19.38-8.13a1.36,1.36,0,0,0,.75-1.72l-.79-2.2A1.36,1.36,0,0,0,182.07,189.63Z" style="fill: #407BFF; transform-origin: 173.302px 196.055px;" id="elngkmvb6nio" class="animable"></path>
                    <path d="M194.4,182.05l.67-.18a4.68,4.68,0,0,1,4.75,1.44l.6.69a1.51,1.51,0,0,1-.57,2.4l-6.59,2.72a1.52,1.52,0,0,1-2-.86h0A4.69,4.69,0,0,1,194.4,182.05Z" style="fill: rgb(181, 91, 82); transform-origin: 195.872px 185.471px;" id="elduw8ktc6kmk" class="animable"></path>
                    <path d="M124.6,168.25l-.5.6-.58.71L122.34,171c-.78,1-1.57,2-2.34,3.07-1.55,2.07-3,4.21-4.47,6.39s-2.79,4.41-4.15,6.65-2.56,4.56-3.75,6.89l-.88,1.76-.21.43a1,1,0,0,1-.08.14,1.44,1.44,0,0,0-.16.35,8.08,8.08,0,0,0-.46,2.33,28.15,28.15,0,0,0,.58,6.76,111.44,111.44,0,0,0,4.25,15.06l.07.19a3.75,3.75,0,0,1-6.5,3.52,61.83,61.83,0,0,1-5-7.38A57.42,57.42,0,0,1,95.36,209,34.61,34.61,0,0,1,93,199.41a20.1,20.1,0,0,1,.31-5.95,12.15,12.15,0,0,1,.44-1.64l.28-.84.2-.53.81-2.06a105.53,105.53,0,0,1,7.84-15.84c1.53-2.53,3.14-5,4.9-7.41.88-1.2,1.78-2.4,2.73-3.56.48-.59,1-1.17,1.47-1.75l.77-.88.89-1a7.5,7.5,0,0,1,11.25,9.9Z" style="fill: rgb(38, 50, 56); transform-origin: 109.728px 190.809px;" id="elobqd5xd9oya" class="animable"></path>
                    <path d="M132.43,156.09c2-.71-.54-2.86-.54-2.86s-10.61.33-16.91,2c-1,.91.14,2,.14,2S126.47,158.2,132.43,156.09Z" style="fill: #407BFF; transform-origin: 123.883px 155.341px;" id="elvo02ergidg" class="animable"></path>
                    <g id="elrfy5f9tey5">
                        <path d="M132.27,153s1.57,5.91,8.71,10.74c-3.12,0-7.54,3.12-7.54,3.12a17.65,17.65,0,0,1-2.69-13.42C131.19,152.75,132.27,153,132.27,153Z" style="opacity: 0.2; transform-origin: 135.688px 159.908px;" class="animable" id="elp5dp1b0v69"></path>
                    </g>
                    <path d="M132.48,152a26.51,26.51,0,0,0,8.5,11.82,32.51,32.51,0,0,0-7.78,1.56s-3.91-3.67-2.45-11.86A6.43,6.43,0,0,1,132.48,152Z" style="fill: rgb(255, 255, 255); transform-origin: 135.702px 158.69px;" id="elxkzh627gl1" class="animable"></path>
                    <path d="M109.62,220.41l6.21,5.35-8.24,5.36s-5-5.53-2.54-8.68Z" style="fill: rgb(181, 91, 82); transform-origin: 110.1px 225.765px;" id="eliybjp8ynqul" class="animable"></path>
                    <path d="M104.19,224.92a5.93,5.93,0,0,1-1.69-2.2c2-2.05,6-3.74,7.79-4.2a5.22,5.22,0,0,1,1.15,3.2S106.17,222.63,104.19,224.92Z" style="fill: rgb(255, 255, 255); transform-origin: 106.97px 221.72px;" id="elctikg82a9lk" class="animable"></path>
                    <path d="M116.54,234.25l-3.21,1.39a2.84,2.84,0,0,1-3.52-1.07l-2.22-3.45,8.24-5.36,2.16,4.69A2.85,2.85,0,0,1,116.54,234.25Z" style="fill: rgb(181, 91, 82); transform-origin: 112.92px 230.817px;" id="elvreosritr9" class="animable"></path>
                    <path d="M141.51,136h0a7.18,7.18,0,0,1,6.6-.68.25.25,0,0,1,.12.34.26.26,0,0,1-.34.12,6.78,6.78,0,0,0-6.12.65.25.25,0,0,1-.34-.09A.24.24,0,0,1,141.51,136Z" style="fill: rgb(38, 50, 56); transform-origin: 144.823px 135.639px;" id="eljnp6aw7em0h" class="animable"></path>
                    <polygon points="125.15 173.51 138.64 173 138.59 171.91 125.11 172.42 125.15 173.51" style="fill: rgb(255, 255, 255); transform-origin: 131.875px 172.71px;" id="elc3f7psn019" class="animable"></polygon>
                </g>
                <g id="freepik--Barricade--inject-62" class="animable" style="transform-origin: 295.77px 341.87px;">
                    <polygon points="241.88 417.46 215.7 417.46 234.73 266.28 260.92 266.28 241.88 417.46" style="fill: #407BFF; transform-origin: 238.31px 341.87px;" id="eldtv0acxccip" class="animable"></polygon>
                    <g id="elqlxkjlphfd">
                        <polygon points="241.88 417.46 215.7 417.46 234.73 266.28 260.92 266.28 241.88 417.46" style="opacity: 0.4; transform-origin: 238.31px 341.87px;" class="animable" id="elk0m79cpdb8m"></polygon>
                    </g>
                    <polygon points="213.36 417.46 215.7 417.46 234.73 266.28 232.39 266.28 213.36 417.46" style="fill: rgb(38, 50, 56); transform-origin: 224.045px 341.87px;" id="elemooex42h6f" class="animable"></polygon>
                    <polygon points="339.53 417.46 313.35 417.46 332.38 266.28 358.57 266.28 339.53 417.46" style="fill: #407BFF; transform-origin: 335.96px 341.87px;" id="el88ia78qegyy" class="animable"></polygon>
                    <g id="elecwdlvd6arh">
                        <polygon points="339.53 417.46 313.35 417.46 332.38 266.28 358.57 266.28 339.53 417.46" style="opacity: 0.4; transform-origin: 335.96px 341.87px;" class="animable" id="elufrzb2nnrb"></polygon>
                    </g>
                    <polygon points="311 417.46 313.35 417.46 332.38 266.28 330.04 266.28 311 417.46" style="fill: rgb(38, 50, 56); transform-origin: 321.69px 341.87px;" id="elzh5n6jsz48" class="animable"></polygon>
                    <polygon points="266.12 399.47 361.08 399.47 356.94 366.56 261.98 366.56 266.12 399.47" style="fill: #407BFF; transform-origin: 311.53px 383.015px;" id="el0q9m4bcri0ck" class="animable"></polygon>
                    <polygon points="266.12 399.47 264.57 399.47 260.42 366.56 261.98 366.56 266.12 399.47" style="fill: rgb(38, 50, 56); transform-origin: 263.27px 383.015px;" id="elohpl38fu2k9" class="animable"></polygon>
                    <polygon points="355.85 368 359.63 398.03 354.02 398.03 338.95 368 355.85 368" style="fill: rgb(255, 255, 255); transform-origin: 349.29px 383.015px;" id="elye4uow3ai0m" class="animable"></polygon>
                    <polygon points="329.38 368 344.45 398.03 323.85 398.03 308.78 368 329.38 368" style="fill: rgb(255, 255, 255); transform-origin: 326.615px 383.015px;" id="eloxu5s1y36sp" class="animable"></polygon>
                    <polygon points="299.21 368 314.28 398.03 293.67 398.03 278.61 368 299.21 368" style="fill: rgb(255, 255, 255); transform-origin: 296.445px 383.015px;" id="elt6u6cj89h1" class="animable"></polygon>
                    <polygon points="269.05 368 284.11 398.03 267.21 398.03 263.43 368 269.05 368" style="fill: rgb(255, 255, 255); transform-origin: 273.77px 383.015px;" id="elx5bemdz24pq" class="animable"></polygon>
                    <polygon points="351.99 417.46 378.18 417.46 359.14 266.28 332.96 266.28 351.99 417.46" style="fill: #407BFF; transform-origin: 355.57px 341.87px;" id="elmhn5mi9jw9m" class="animable"></polygon>
                    <g id="el2epk84o332p">
                        <polygon points="341.44 333.63 368.99 344.46 361.08 281.68 334.89 281.68 341.44 333.63" style="opacity: 0.2; transform-origin: 351.94px 313.07px;" class="animable" id="el8j5o2ay2g69"></polygon>
                    </g>
                    <polygon points="351.99 417.46 349.65 417.46 330.62 266.28 332.96 266.28 351.99 417.46" style="fill: rgb(38, 50, 56); transform-origin: 341.305px 341.87px;" id="el0x1b72zk8he" class="animable"></polygon>
                    <polygon points="254.34 417.46 280.53 417.46 261.5 266.28 235.31 266.28 254.34 417.46" style="fill: #407BFF; transform-origin: 257.92px 341.87px;" id="elxz1nqoe6ld" class="animable"></polygon>
                    <g id="elo2ldsp11pzp">
                        <polygon points="243.79 333.63 271.34 344.46 263.43 281.68 237.25 281.68 243.79 333.63" style="opacity: 0.2; transform-origin: 254.295px 313.07px;" class="animable" id="elvkvllyfr62e"></polygon>
                    </g>
                    <polygon points="254.34 417.46 252 417.46 232.97 266.28 235.31 266.28 254.34 417.46" style="fill: rgb(38, 50, 56); transform-origin: 243.655px 341.87px;" id="elbnbz5nkrv1q" class="animable"></polygon>
                    <polygon points="233.33 326.66 376.32 326.66 370.08 277.11 227.09 277.11 233.33 326.66" style="fill: #407BFF; transform-origin: 301.705px 301.885px;" id="elaylmjfiati8" class="animable"></polygon>
                    <polygon points="233.33 326.66 230.99 326.66 224.75 277.11 227.09 277.11 233.33 326.66" style="fill: rgb(38, 50, 56); transform-origin: 229.04px 301.885px;" id="elxszpwd321k9" class="animable"></polygon>
                    <polygon points="368.44 279.27 374.14 324.49 365.69 324.49 343 279.27 368.44 279.27" style="fill: rgb(255, 255, 255); transform-origin: 358.57px 301.88px;" id="elh985f448ghc" class="animable"></polygon>
                    <polygon points="328.59 279.27 351.28 324.49 320.25 324.49 297.57 279.27 328.59 279.27" style="fill: rgb(255, 255, 255); transform-origin: 324.425px 301.88px;" id="elc3mi4z1440g" class="animable"></polygon>
                    <polygon points="283.16 279.27 305.85 324.49 274.82 324.49 252.13 279.27 283.16 279.27" style="fill: rgb(255, 255, 255); transform-origin: 278.99px 301.88px;" id="el0pr92yo6t2qf" class="animable"></polygon>
                    <polygon points="237.74 279.27 260.42 324.49 234.97 324.49 229.28 279.27 237.74 279.27" style="fill: rgb(255, 255, 255); transform-origin: 244.85px 301.88px;" id="ela1796zwvjnn" class="animable"></polygon>
                </g>
                <defs>
                    <filter id="active" height="200%">
                        <feMorphology in="SourceAlpha" result="DILATED" operator="dilate" radius="2"></feMorphology>
                        <feFlood flood-color="#32DFEC" flood-opacity="1" result="PINK"></feFlood>
                        <feComposite in="PINK" in2="DILATED" operator="in" result="OUTLINE"></feComposite>
                        <feMerge>
                            <feMergeNode in="OUTLINE"></feMergeNode>
                            <feMergeNode in="SourceGraphic"></feMergeNode>
                        </feMerge>
                    </filter>
                    <filter id="hover" height="200%">
                        <feMorphology in="SourceAlpha" result="DILATED" operator="dilate" radius="2"></feMorphology>
                        <feFlood flood-color="#ff0000" flood-opacity="0.5" result="PINK"></feFlood>
                        <feComposite in="PINK" in2="DILATED" operator="in" result="OUTLINE"></feComposite>
                        <feMerge>
                            <feMergeNode in="OUTLINE"></feMergeNode>
                            <feMergeNode in="SourceGraphic"></feMergeNode>
                        </feMerge>
                        <feColorMatrix type="matrix" values="0   0   0   0   0                0   1   0   0   0                0   0   0   0   0                0   0   0   1   0 "></feColorMatrix>
                    </filter>
                </defs>
            </svg>
        </div>

        <div class="text-center">
            <h3 class="mt-4">Access Denied</h3>
            <p class="text-muted mb-0">You don't have permission to access this resource. Please verify your credentials or contact your administrator if you believe this is an error. Here's a few suggestions to help you get back on track:</p>
        </div>

    </div> <!-- end card-body -->
</div>
<!-- end card -->

<div class="row mt-3">
    <div class="col-12 text-center">
        <p class="text-white-50">Return to <a href="<?= view('auth.index') ?>" class="text-white ms-1"><b>Home</b></a></p>
    </div> <!-- end col -->
</div>
<!-- end row -->

<?php
$content = ob_get_clean();
include layouts('auth.main');
