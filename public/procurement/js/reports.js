    // FUNCTIONALITY PARA SA MGA BUTTONS

        // 1. DONE (Return to index.php)
        function handleDone() {
            console.log("Returning to index.php...");
            // Dahil ito ay HTML/JS lang, gagamitin natin ang location.href para ipakita ang intended action
            // window.location.href = 'index.php'; 
            
            // Custom message box to show the action without alert()
            const messageBox = document.createElement('div');
            messageBox.innerHTML = `
                <div class="fixed inset-0 bg-gray-600 bg-opacity-70 flex items-center justify-center z-50 transition-opacity duration-300">
                    <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full animate-pulse-once">
                        <p class="text-xl font-bold text-gray-800">Redirecting...</p>
                        <p class="text-gray-600 mt-2">Ito ay babalik sana sa <span class="font-mono text-sm bg-gray-200 p-1 rounded">index.php</span>.</p>
                        <button onclick="this.parentNode.parentNode.remove()" class="mt-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg float-right transition">Sige, Isara</button>
                    </div>
                </div>
            `;
            document.body.appendChild(messageBox);
        }

        // 2. PRINT
        function handlePrint() {
            window.print();
        }

        // 3. DOWNLOAD (Generate PDF)
        async function handleDownload() {
            // I-disable ang button at magpakita ng loading status
            const downloadButton = document.querySelector('.js-download-btn');
            const originalText = downloadButton.textContent;
            downloadButton.textContent = 'Generating PDF...';
            downloadButton.disabled = true;
            
            // Ang element na gustong i-convert sa PDF (ang report)
            const element = document.getElementById('monthly-report-content'); 

            // Configuration ng PDF (Letter size, Portrait)
            const options = {
                margin: 0.5,
                filename: 'Monthly_Receiving_Report_October_2025.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, logging: false, scrollY: 0 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' } // Landscape para sa maraming columns
            };

            // I-convert at I-download
            await html2pdf().set(options).from(element).save();

            // Ibalik sa orihinal ang button
            downloadButton.textContent = originalText;
            downloadButton.disabled = false;
        }