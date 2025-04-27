// static/script.js
document.getElementById('prediction-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/predict', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('results').innerHTML = `
                <h3>Recommended Crops:</h3>
                <ul>
                    ${result.predictions.map(crop => `<li>${crop}</li>`).join('')}
                </ul>
            `;
        } else {
            alert('Prediction failed');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
});