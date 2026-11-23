# Dream Smurfer

The basic idea is this will smurf a copy of the dreams in my journal and use AI to look for interesting patterns. 

Dreams in my journal = files in my journal with the word 'dream' (case insensitive) in the filename.

My journal is a separate repo with files in YYYY/MM/DDtitle.ext


## Deploying

Run `./deploy_dreams_to_dh.sh`. It pushes the current branch, then runs `git pull` on the server (ssh alias `quick`).
