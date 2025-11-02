  // FUNCTIONALITY FOR THE BUTTONS

        // 1. DONE (Return to Index)
        function handleDone() {
            // Sa totoong ERP system, ito ay babalik sa main dashboard o PO list
            // Dahil wala tayong index.html, magpapakita lang tayo ng message.
            console.log("Returning to Index/Dashboard...");
            // window.location.href = 'index.html'; 
            alert("Done! Returning to main menu is not possible in this isolated view, but this function should redirect you to index.html.");
        }

        // 2. PRINT
        function handlePrint() {
            window.print();
        }
// 3. DOWNLOAD (Generate PDF)
        async function handleDownload() {
            // I-disable ang button habang nag-ge-generate ng PDF
            const downloadButton = document.querySelector('.js-download-btn');
            const originalText = downloadButton.textContent;
            downloadButton.textContent = 'Generating PDF...';
            downloadButton.disabled = true;
            
            // Ang element na gustong i-convert sa PDF (ang resibo)
            const element = document.getElementById('grn-content'); 

            // Configuration ng PDF
            const options = {
                margin: 0.5,
                filename: 'GRN_Accounting_Report.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, logging: true, scrollY: 0 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            // I-convert at I-download
            await html2pdf().set(options).from(element).save();

            // Ibalik sa orihinal ang button
            downloadButton.textContent = originalText;
            downloadButton.disabled = false;
        }