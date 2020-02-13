{{$result}}
<script type="text/javascript">
    window.onload = () => {
        window.addEventListener('message', (e) => {
            const innerText = document.body.innerText
            if (e.data === 'response back') {
                if (innerText.includes('was deleted')) {
                    e.source.postMessage('restyle clicked button to success', '*')
                } else {
                    e.source.postMessage('restyle clicked button to error', '*')
                }
            }
        })
    }
</script>
