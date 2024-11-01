document.addEventListener("DOMContentLoaded", () => {
    if (typeof sureformsData === "undefined") {
        console.error("SureForms data is not available.");
        return;
    }

    document.addEventListener("SRFM_Form_Success_Message", async (t) => {
        const selectedForms = sureformsData.selectedForms;
        const sessionId = sureformsData.sessionId;
        const simulateClipboard = sureformsData.simulateClipboard ?? true;
        const simulateAttachment = sureformsData.simulateAttachment ?? true;
        const { form, container } = t.detail;

        let formId = null;
        if (form && form.id) {
            const formIdMatch = form.id.match(/srfm-form-(\d+)/);
            if (formIdMatch) {
                formId = formIdMatch[1];
            }
        }

        if (formId && selectedForms[formId] && selectedForms[formId].enabled) {
            t.preventDefault();

            // Show spinner on submit button
            const submitButton = form.querySelector("#srfm-submit-btn");
            const loader = submitButton.querySelector(".srfm-loader");
            if (submitButton && loader) {
                submitButton.disabled = true;
                submitButton.querySelector(".srfm-submit-wrap").style.opacity = "0.7"; // Optional: slightly dim button
                loader.style.display = "inline-block"; // Show the loader
            }

            const maxRetries = 30;
            const interval = 3000;
            let retries = 0;
            let responseReceived = false;

            while (retries < maxRetries && !responseReceived) {
                await new Promise(resolve => setTimeout(resolve, interval));
                
                retries++;

                if (retries === 7) {
                    const simulatedResponse = {
                        body: "<p>Thank you for your submission. Here is your response data.</p>",
                        attachment: simulateAttachment ? {
                            status: 200,
                            html: "<div class='attachments-content'><a href='https://example.com/download/file.pdf' class='srfm-download-link' target='_blank'>Download PDF</a></div>"
                        } : null
                    };

                    let customContent = `<div>${simulatedResponse.body}</div>`;
                    if (simulateClipboard) {
                        customContent += `
                            <button class="srfm-copy-button" onclick="navigator.clipboard.writeText('${simulatedResponse.body.replace(/<[^>]*>/g, '')}')">
                                Copy to Clipboard
                            </button>`;
                    }
                    if (simulatedResponse.attachment && simulatedResponse.attachment.status === 200) {
                        customContent += simulatedResponse.attachment.html;
                    }

                    if (container) {
                        container.innerHTML = customContent;
                        container.style.display = 'block';
                    }
                    responseReceived = true;
                }
            }

            if (!responseReceived) {
                const timeoutMessage = "<p>Sorry, no response received within the time limit. Please try again later.</p>";
                if (container) {
                    container.innerHTML = timeoutMessage;
                    container.style.display = 'block';
                }
            }

            // Re-enable submit button and hide the loader
            if (submitButton && loader) {
                submitButton.disabled = false;
                submitButton.querySelector(".srfm-submit-wrap").style.opacity = "1"; // Reset opacity
                loader.style.display = "none"; // Hide the loader
            }
        }
    });
});
