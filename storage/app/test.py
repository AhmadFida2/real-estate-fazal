import json
import random
import string
import sys

def generate_random_string(length=15):
    characters = string.ascii_letters + string.digits
    random_string = ''.join(random.choice(characters) for _ in range(length))
    return random_string

f_name = 'storage/' + sys.argv[1]
random_string = generate_random_string()
file = open(f_name,'r')
data = file.read()
data = json.loads(data)
file.close()
file = open('storage/'+random_string+'.txt','w')
file.write(json.dumps(data))
file.close
print(random_string)
