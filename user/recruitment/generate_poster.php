<?php
// This line should be at the very top of the file, before any other code.
ob_start();

/**
 * user/recruitment/generate_poster.php
 *
 * This file allows Data Entry Operators (DEOs) to generate and download
 * recruitment image banners for use on platforms like Google Blog.
 * It ensures that only authenticated DEO users can access this page.
 */

// Include the main configuration file
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

// Restrict access to Data Entry Operator users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'data_entry_operator') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Recruitment Poster Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <!-- Phosphor Icons for a wide range of icons -->
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1/dist/phosphor.js"></script> 
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap'); /* Updated font import from working.php */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        /* Custom scrollbar for settings panel */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #a0a0a0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #808080;
        }
        /* Styling for dynamic text containers within the poster preview */
        .dynamic-text-container {
            word-break: break-word;
            overflow: hidden; /* Default to hidden for preview */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.2rem;
            line-height: 1.2;
            white-space: pre-wrap; /* Preserve whitespace and allow wrapping */
            /* No default color here, designs will provide it */
        }
        /* Fixed poster size for consistent output, matching working.php's 1080px */
        #posterPreview {
            width: 870px;
            height: 1080px; /* MATCHING WORKING.PHP'S HEIGHT */
            flex-shrink: 0; /* Prevents shrinking on smaller screens */
            position: relative;
            transform-origin: top left; /* Ensures consistent scaling */
            transform: scale(1);
            background-color: #ffffff; /* Default background */
            border-radius: 30px;
            overflow: hidden; /* Ensures content stays within rounded corners */
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); /* Soft shadow for depth */
        }
        
        /* Base Poster Layout Styles - Copied exactly from working.php for consistency */
        .poster-container {
            border: 2px solid #e0e7ee;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 100%; /* Ensure it takes full width of #posterPreview */
            height: 100%; /* Ensure it takes full height of #posterPreview */
        }
        .poster-card {
            background: #fff;
            border-radius: 20px; /* Matching working.php */
            padding: 1.5rem; /* Matching working.php */
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); /* Matching working.php */
            transition: transform 0.2s ease, box-shadow 0.2s ease; /* From working.php */
            height: 100%; /* Ensures cards have a fixed height (flex-grow handles remaining space) */
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent content overflow */
            justify-content: flex-start; /* Align content to top */
            align-items: center;
            text-align: center;
        }
        .poster-card-title {
            font-size: 1.8rem; /* Matching working.php */
            font-weight: 700;
            margin-bottom: 0.6rem; /* Matching working.php */
            padding-bottom: 0.3rem; /* Matching working.php */
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem; /* Matching working.php */
            width: 100%;
            /* No default color here, designs will provide it */
        }
        .poster-card-title .ph-icon {
            font-size: 2rem; /* Matching working.php */
        }
        .dynamic-text-container {
            flex-grow: 1; /* Allows text container to fill available space */
            align-items: flex-start; /* Align text to the top */
        }
        
        /* --- DESIGN STYLES (COPIED EXACTLY FROM working.php) --- */
        /* These 5 designs replace the previous 6 from the generate_poster.php */

        /* Design 1: Professional Blue (from working.php) */
        .design-1 { background: linear-gradient(145deg, #f0f4f8 0%, #e6e9f0 100%); }
        .design-1 .poster-card { background: #fff; }
        .design-1 .poster-card-title { color: #1a237e; border-bottom: 2px solid #c5cae9; }
        .design-1 .ph-icon { color: #5c6bc0; }
        .design-1 .poster-footer { background: #1a237e; color: #e8eaf6; }

        /* Design 2: Vibrant Orange (from working.php) */
        .design-2 { background: linear-gradient(145deg, #fff3e0 0%, #ffcc80 100%); }
        .design-2 .poster-card { background: #fff; border-left: 5px solid #ff9800; }
        .design-2 .poster-card-title { color: #e65100; border-bottom: 2px dashed #ffb74d; }
        .design-2 .ph-icon { color: #ff9800; }
        .design-2 .poster-footer { background: #ff9800; color: #fffde7; }

        /* Design 3: Modern Dark (from working.php) */
        .design-3 { background: #263238; color: #eceff1; }
        .design-3 .poster-card { background: #37474f; color: #eceff1; }
        .design-3 .poster-card-title { color: #b2ebf2; border-bottom: 2px solid #546e7a; }
        .design-3 .ph-icon { color: #b2ebf2; }
        .design-3 .poster-footer { background: #37474f; color: #b2ebf2; }

        /* Design 4: Playful Green (from working.php) */
        .design-4 { background: linear-gradient(145deg, #e8f5e9 0%, #a5d6a7 100%); }
        .design-4 .poster-card { background: #f7fcf9; border-right: 5px solid #4caf50; }
        .design-4 .poster-card-title { color: #1b5e20; border-bottom: 2px dotted #c8e6c9; }
        .design-4 .ph-icon { color: #4caf50; }
        .design-4 .poster-footer { background: #2e7d32; color: #e8f5e9; }
        
        /* Design 5: Elegant Purple (from working.php) */
        .design-5 { background: linear-gradient(145deg, #f3e5f5 0%, #e1bee7 100%); }
        .design-5 .poster-card { background: #fff; border: 1px solid #ce93d8; }
        .design-5 .poster-card-title { color: #4a148c; border-bottom: 2px solid #ce93d8; }
        .design-5 .ph-icon { color: #8e24aa; }
        .design-5 .poster-footer { background: #6a1b9a; color: #f3e5f5; }


        @media (max-width: 1023px) {
            .poster-preview-container { padding: 1rem 0.5rem; }
            #posterPreview { width: 425px; height: 540px; margin-top: 1rem; } /* Matching working.php */
            .poster-card-title { font-size: 1.2rem; }
            .poster-card-title .ph-icon { font-size: 1.4rem; }
            .poster-card { padding: 0.8rem; }
            .poster-header { height: 110px; padding: 1rem; } /* Matching working.php */
            .poster-header .w-\[120px\] { width: 80px; height: 80px; } /* Matching working.php */
            .poster-header #organizationNameDisplay { font-size: 1.5rem; }
            .poster-header #recruitmentTitleDisplay { font-size: 1.3rem; }
            .poster-footer { height: 130px; padding: 0.8rem; } /* Matching working.php */
            .poster-footer .info-item { font-size: 0.9rem; }
            .poster-footer .info-item .ph-icon { font-size: 1.2rem; }
            .poster-footer .w-\[100px\] { width: 60px; height: 60px; } /* Matching working.php */
        }
    </style>
</head>
<body class="flex flex-col lg:flex-row min-h-screen">
    <div class="flex flex-col lg:flex-row w-full p-4 lg:p-8 space-y-8 lg:space-y-0 lg:space-x-8">
        <div class="w-full lg:w-1/3 p-4 bg-gray-50 rounded-lg shadow-inner overflow-y-auto max-h-screen custom-scrollbar">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Poster Settings</h2>
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Main Poster Details</h3>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Select Poster Design:</span>
                    <select id="designSelector" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <option value="design-1">Design 1: Professional Blue</option>
                        <option value="design-2">Design 2: Vibrant Orange</option>
                        <option value="design-3">Design 3: Modern Dark</option>
                        <option value="design-4">Design 4: Playful Green</option>
                        <option value="design-5">Design 5: Elegant Purple</option>
                    </select>
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Organization Name:</span>
                    <input type="text" id="organizationName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., Google Inc.">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Recruitment Title:</span>
                    <input type="text" id="recruitmentTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., Combined Graduate Level Examination">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Total Vacancies:</span>
                    <input type="text" id="numberOfVacancies" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., 2500 Vacancies">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Application Start Date:</span>
                    <input type="date" id="applicationStartDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Application Deadline:</span>
                    <input type="date" id="applicationDeadline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Eligibility Criteria:</span>
                    <textarea id="eligibilityCriteria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 h-24 resize-y" placeholder="e.g., Bachelor's Degree in any stream from a recognized University."></textarea>
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Age Limit:</span>
                    <textarea id="ageLimit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 h-20 resize-y" placeholder="e.g., 18-27 years as on 01.01.2025. Age relaxation as per rules."></textarea>
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Fees Details:</span>
                    <textarea id="feesDetails" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 h-20 resize-y" placeholder="e.g., General / OBC / EWS: ₹ 100/- SC / ST / PWD / Female: ₹ 0/-"></textarea>
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Official Website:</span>
                    <input type="text" id="officialWebsiteUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., www.example.com">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Company Website (Optional):</span>
                    <input type="text" id="companyWebsiteUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2" placeholder="e.g., www.yourcompany.com">
                </label>
            </div>
            <div class="mb-8 border-t pt-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Image Settings</h3>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Upload Official Logo:</span>
                    <input type="file" id="officialLogoUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </label>
                <label class="block mb-4">
                    <span class="text-gray-700 font-medium">Upload Company Logo:</span>
                    <input type="file" id="companyLogoUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </label>
            </div>
            <div class="mt-8 flex justify-center lg:justify-start">
                <button id="downloadPoster" class="bg-indigo-600 text-white font-bold py-3 px-6 rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-300 ease-in-out transform hover:scale-105">
                    Download Poster
                </button>
            </div>
        </div>
        <div class="w-full lg:w-2/3 flex items-start justify-center p-4">
            <div id="posterPreviewContainer" class="rounded-lg shadow-xl overflow-hidden">
                <!-- Poster content will be dynamically generated here -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const els = {
                designSelector: document.getElementById('designSelector'),
                organizationName: document.getElementById('organizationName'),
                recruitmentTitle: document.getElementById('recruitmentTitle'),
                numberOfVacancies: document.getElementById('numberOfVacancies'),
                applicationStartDate: document.getElementById('applicationStartDate'),
                applicationDeadline: document.getElementById('applicationDeadline'),
                eligibilityCriteria: document.getElementById('eligibilityCriteria'),
                ageLimit: document.getElementById('ageLimit'),
                feesDetails: document.getElementById('feesDetails'),
                officialWebsiteUrl: document.getElementById('officialWebsiteUrl'),
                companyWebsiteUrl: document.getElementById('companyWebsiteUrl'),
                officialLogoUpload: document.getElementById('officialLogoUpload'),
                companyLogoUpload: document.getElementById('companyLogoUpload'),
                posterPreviewContainer: document.getElementById('posterPreviewContainer'),
                downloadPosterBtn: document.getElementById('downloadPoster'),
            };

            // Default placeholder URLs for logos (using darker backgrounds for better visibility of placeholder text) - Matching working.php
            let customOfficialLogoUrl = 'https://placehold.co/120x120/ffffff/000000?text=Logo';
            let customCompanyLogoUrl = 'https://placehold.co/120x120/ffffff/000000?text=Company'; 
            
            const debouncedUpdate = debounce(updatePosterPreview, 300);

            function debounce(func, delay) {
                let timeout;
                return function(...args) {
                    const context = this;
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(context, args), delay);
                };
            }

            /**
             * Function to fit text into a fixed container by adjusting font size.
             * It gracefully handles multi-line text and respects a minimum font size.
             * THIS IS NOW THE SAME AS working.php's version of this function
             */
            function fitTextToContainer(element, initialFontSize) {
                if (!element) return;
                const container = element.parentElement;
                if (!container) return;
            
                // Reset font size to initial to get correct scrollHeight
                element.style.fontSize = initialFontSize + 'rem';
                element.style.lineHeight = 1.2;
            
                let currentFontSize = initialFontSize;
                let safetyCounter = 200;
            
                // Use a smaller decrement for more precise fitting
                const decrement = 0.05;
                const minFontSize = 0.5;
            
                while (element.scrollHeight > container.clientHeight && safetyCounter > 0 && currentFontSize > minFontSize) {
                    currentFontSize -= decrement;
                    element.style.fontSize = currentFontSize + 'rem';
                    safetyCounter--;
                }
            }


            // Handles file uploads and sets the image URL - NOW SAME AS working.php
            function handleLogoUpload(inputElement, callback) {
                const file = inputElement.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        callback(e.target.result);
                    };
                    reader.readAsDataURL(file);
                } else {
                    if (inputElement.id === 'officialLogoUpload') {
                        customOfficialLogoUrl = 'https://placehold.co/120x120/ffffff/000000?text=Logo';
                    } else if (inputElement.id === 'companyLogoUpload') {
                        customCompanyLogoUrl = 'https://placehold.co/120x120/ffffff/000000?text=Company';
                    }
                    updatePosterPreview();
                }
            }

            function getFormattedDate(dateStr) {
                if (!dateStr) return 'dd/mm/yyyy';
                const [year, month, day] = dateStr.split('-');
                return `${day}/${month}/${year}`;
            }

            function updatePosterPreview() {
                const selectedDesign = els.designSelector.value;
                // Default content now matches working.php's logic, without spans for text-color
                const data = {
                    organizationName: els.organizationName.value || 'Organization Name',
                    recruitmentTitle: els.recruitmentTitle.value || 'Recruitment Title',
                    numberOfVacancies: els.numberOfVacancies.value || '0000',
                    applicationStartDate: getFormattedDate(els.applicationStartDate.value) || 'dd/mm/yyyy',
                    applicationDeadline: getFormattedDate(els.applicationDeadline.value) || 'dd/mm/yyyy',
                    eligibilityCriteria: els.eligibilityCriteria.value || 'Eligibility details will be displayed here.',
                    ageLimit: els.ageLimit.value || 'Age limit details will be displayed here.',
                    feesDetails: els.feesDetails.value || 'Fee details will be displayed here.',
                    officialWebsiteUrl: els.officialWebsiteUrl.value || 'www.example.com',
                    companyWebsiteUrl: els.companyWebsiteUrl.value || 'www.bronline.net', // Hardcoded as in working.php if empty
                    officialLogoUrl: customOfficialLogoUrl,
                    companyLogoUrl: customCompanyLogoUrl,
                };
                
                // Poster HTML template with fixed dimensions and improved layout - Now matches working.php's structure and styles
                const posterHtml = `
                    <div id="posterPreview" class="relative poster-container ${selectedDesign}">
                        <!-- Header section - Matching working.php structure -->
                        <div class="h-[160px] p-[1.5rem] flex items-center justify-between bg-white bg-opacity-85 shadow-md rounded-t-[20px]">
                            <div class="w-[120px] h-[120px] bg-white p-[6px] rounded-[14px] shadow-lg flex items-center justify-center flex-shrink-0">
                                <img src="${data.officialLogoUrl}" alt="Official Logo" class="w-full h-full object-contain p-[4px] rounded-[10px]" crossorigin="anonymous">
                            </div>
                            <div class="flex-grow text-center mx-[0.8rem] flex flex-col justify-center items-center h-full">
                                <div id="organizationNameDisplay" class="text-[2.3rem] font-extrabold text-red-600 dynamic-text-container">${data.organizationName}</div>
                                <div id="recruitmentTitleDisplay" class="text-[2rem] font-bold text-indigo-700 mt-[0.5rem] dynamic-text-container">${data.recruitmentTitle}</div>
                            </div>
                        </div>

                        <!-- Main grid content - Matching working.php structure -->
                        <div class="p-[1.5rem] flex-grow grid grid-cols-2 gap-6">
                            <!-- Eligibility Card (Top Left) - Matching working.php structure and fixed height -->
                            <div class="poster-card flex flex-col justify-start h-[200px]">
                                <h4 class="poster-card-title pb-[0.3rem] w-full mb-[0.5rem]">
                                    <i class="ph ph-graduation-cap ph-icon"></i> Eligibility
                                </h4>
                                <div id="eligibilityCriteriaDisplay" class="text-[1.2rem] font-medium text-gray-700 dynamic-text-container flex-grow">${data.eligibilityCriteria}</div>
                            </div>
                            <!-- Total Vacancies Card (Top Right) - Matching working.php structure and fixed height -->
                            <div class="poster-card flex flex-col justify-start h-[200px]">
                                <h4 class="poster-card-title pb-[0.3rem] w-full mb-[0.5rem]">
                                    <i class="ph ph-briefcase ph-icon"></i> Total Vacancies
                                </h4>
                                <div id="numberOfVacanciesDisplay" class="text-[3rem] font-extrabold text-green-600 dynamic-text-container">${data.numberOfVacancies}</div>
                            </div>
                            <!-- Fees Card (Bottom Left) - Matching working.php structure and fixed height -->
                            <div class="poster-card flex flex-col justify-start h-[160px]">
                                <h4 class="poster-card-title pb-[0.3rem] w-full mb-[0.5rem]">
                                    <i class="ph ph-currency-dollar ph-icon"></i> Fees
                                </h4>
                                <div id="feesDetailsDisplay" class="text-[1.2rem] font-medium text-gray-700 dynamic-text-container flex-grow">${data.feesDetails}</div>
                            </div>
                             <!-- Age Limit Card (Bottom Right) - Matching working.php structure and fixed height -->
                            <div class="poster-card flex flex-col justify-start h-[160px]">
                                <h4 class="poster-card-title pb-[0.3rem] w-full mb-[0.5rem]">
                                    <i class="ph ph-user-circle ph-icon"></i> Age Limit
                                </h4>
                                <div id="ageLimitDisplay" class="text-[1.2rem] font-medium dynamic-text-container flex-grow">${data.ageLimit}</div>
                            </div>
                        </div>

                        <!-- Footer Section - Matching working.php structure -->
                        <div class="h-[180px] p-[1.5rem] flex flex-col justify-around items-center rounded-b-[20px] poster-footer">
                            <div class="flex justify-around w-full mb-[1rem]">
                                <!-- Start Date -->
                                <div class="flex flex-col items-center">
                                    <p class="font-light text-sm text-current">Start Date</p>
                                    <div id="startDateDisplay" class="mt-[0.2rem] text-[1.5rem] font-extrabold text-current">${data.applicationStartDate}</div>
                                </div>
                                <!-- Deadline Date -->
                                <div class="flex flex-col items-center">
                                    <p class="font-light text-sm text-current">Deadline</p>
                                    <div id="endDateDisplay" class="mt-[0.2rem] text-[1.5rem] font-extrabold text-red-400">${data.applicationDeadline}</div>
                                </div>
                            </div>
                            <!-- Website Links -->
                            <div class="w-full text-center flex flex-col items-center">
                                <div id="officialWebsiteUrlDisplay" class="text-[1rem] font-semibold dynamic-text-container mb-[0.2rem]">
                                    <i class="ph ph-globe ph-icon"></i> Official Website: ${data.officialWebsiteUrl}
                                </div>
                                <div id="companyWebsiteUrlDisplay" class="text-[1rem] font-semibold dynamic-text-container">
                                    <i class="ph ph-buildings ph-icon"></i> Company Website: ${data.companyWebsiteUrl}
                                </div>
                            </div>
                            <!-- Company Logo in Footer - Matching working.php size -->
                            <div class="w-[80px] h-[80px] bg-white p-[4px] rounded-[10px] shadow-md flex items-center justify-center mt-[1rem]">
                                <img src="${data.companyLogoUrl}" alt="Company Logo" class="w-full h-full object-contain p-[2px] rounded-[8px]" crossorigin="anonymous">
                            </div>
                        </div>
                    </div>
                `;

               els.posterPreviewContainer.innerHTML = posterHtml;

                setTimeout(() => {
                    // Apply font size fitting to the main titles and content fields
                    fitTextToContainer(document.getElementById('organizationNameDisplay'), 2.3);
                    fitTextToContainer(document.getElementById('recruitmentTitleDisplay'), 2.0);
                    fitTextToContainer(document.getElementById('numberOfVacanciesDisplay'), 3.0);
                    fitTextToContainer(document.getElementById('eligibilityCriteriaDisplay'), 1.2);
                    fitTextToContainer(document.getElementById('ageLimitDisplay'), 1.2);
                    fitTextToContainer(document.getElementById('feesDetailsDisplay'), 1.2);
                    fitTextToContainer(document.getElementById('officialWebsiteUrlDisplay'), 1.0);
                    fitTextToContainer(document.getElementById('companyWebsiteUrlDisplay'), 1.0);
                }, 100);
            }

            els.downloadPosterBtn.addEventListener('click', function() {
                const posterElement = document.getElementById('posterPreview');
                if (!posterElement) {
                    console.error('Poster element not found.');
                    return;
                }
                
                // Temporarily adjust overflow to visible for html2canvas to capture full text
                const dynamicTextContainers = posterElement.querySelectorAll('.dynamic-text-container');
                const originalOverflows = [];
                dynamicTextContainers.forEach(container => {
                    originalOverflows.push(container.style.overflow);
                    container.style.overflow = 'visible';
                });

                html2canvas(posterElement, {
                    scale: 2, // Higher scale for better quality download
                    useCORS: true,
                    allowTaint: true,
                }).then(canvas => {
                    // Restore original overflow styles
                    dynamicTextContainers.forEach((container, index) => {
                        container.style.overflow = originalOverflows[index];
                    });

                    const link = document.createElement('a');
                    link.download = 'recruitment_poster.png';
                    link.href = canvas.toDataURL('image/png');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            });

            // Event listeners for all input fields and the design selector
            Object.values(els).forEach(el => {
                if (el && el !== els.downloadPosterBtn && el !== els.officialLogoUpload && el !== els.companyLogoUpload) {
                    el.addEventListener('input', debouncedUpdate);
                }
            });

            els.officialLogoUpload.addEventListener('change', function() {
                handleLogoUpload(this, (url) => {
                    customOfficialLogoUrl = url;
                    updatePosterPreview();
                });
            });

            els.companyLogoUpload.addEventListener('change', function() {
                handleLogoUpload(this, (url) => {
                    customCompanyLogoUrl = url;
                    updatePosterPreview();
                });
            });

            // Initial call to set up the poster with the default design
            updatePosterPreview();
        });
    </script>
</body>
</html>
<?php
// This line should be at the very end of the file, after all other code.
ob_end_flush();
?>
