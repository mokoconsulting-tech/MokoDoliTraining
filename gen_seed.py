Q = chr(39)
T  = chr(9)
TT = chr(9)*2
TTT= chr(9)*3
TTTT=chr(9)*4
L  = []
def a(*args):
    for x in args: L.append(x)

def q(s): return Q+s+Q
