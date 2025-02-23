const useEffect = window.React.useEffect

export default function(props) {

  useEffect(()=>{ window.location.search = '?page=wc-admin&path=%2Funuspay%2Fsettings' }, [])

  return(null)
}
