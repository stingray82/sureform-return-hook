document.addEventListener("DOMContentLoaded", () => {
    if (typeof sureformsData === "undefined") {
        console.error("SureForms data is not available.");
        return;
    }

    document.addEventListener("SRFM_Form_Success_Message", async (t) => {
        const selectedForms = sureformsData.selectedForms;
        const sessionId = sureformsData.sessionId;
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

            // Polling variables for waiting on FlowMattic response
            const maxRetries = 30;
            const interval = 3000; // Poll every 3 seconds
            let retries = 0;
            let responseReceived = false;

            // Start polling to check for FlowMattic response
            while (retries < maxRetries && !responseReceived) {
                await new Promise(resolve => setTimeout(resolve, interval));
                retries++;

                // Simulate checking for FlowMattic response (replace with actual API call logic)
                const flowmatticResponse = await fetchFlowMatticResponse(formId, sessionId);

                // Process the response if received
                if (flowmatticResponse && flowmatticResponse.status === "success") {
                    responseReceived = true;

                    // Format the response using the PHP function output (simulated here)
                    let customContent = `<div>${flowmatticResponse.body}</div>`;
                    if (flowmatticResponse.attachment && flowmatticResponse.attachment.status === 200) {
                        customContent += flowmatticResponse.attachment.html;
                    }

                    if (container) {
                        container.innerHTML = customContent;
                        container.style.display = 'block';
                    }
                }
            }

            // Show timeout message if no response received
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

// Mock function to simulate fetching a response from FlowMattic
async function fetchFlowMatticResponse(formId, sessionId) {
    try {
        const response = await fetch(`/wp-json/sureforms/v1/check-response?form_id=${formId}&session_id=${sessionId}`);
        if (!response.ok) {
            return null;
        }
        return await response.json();
    } catch (error) {
        console.error("Error fetching FlowMattic response:", error);
        return null;
    }
}
