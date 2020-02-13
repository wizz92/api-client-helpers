{{$result}}
<script type="text/javascript">
    window.onload = () => {
        window.addEventListener('message', (e) => {
            if (e.data === 'response back') {
                if (innerText.includes('success') || innerText.includes('was deleted')) {
                    e.source.postMessage('restyle clicked button to success', '*')
                } else {
                    e.source.postMessage('restyle clicked button to error', '*')
                }
            }
        })
    }
</script>